<?php

namespace App\Services\Pagos;

use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionRecord;
use App\Models\LactoscanAnalysis;
use App\Models\ProducerDeduction;
use App\Models\ProducerSettlement;
use App\Models\SystemPrice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Liquidación semanal del proveedor: cálculo del ciclo abierto, autorización
 * por administración y entrega del sobre en efectivo en la ruta del viernes.
 *
 * Regla de Huata: si se detecta agua en CUALQUIER día del ciclo, la penalidad
 * por litro se aplica a TODOS los litros de la semana, no solo a ese día.
 */
class LiquidacionService
{
    /**
     * Rango del ciclo abierto: desde el día siguiente a la última liquidación
     * pagada; si nunca se le pagó, los últimos 7 días.
     */
    public function rangoCicloAbierto(User $productor): array
    {
        $hoy = Carbon::today()->format('Y-m-d');

        $ultimaPagada = ProducerSettlement::where('producer_id', $productor->id)
            ->where('status', 'pagado')
            ->orderByDesc('end_date')
            ->first();

        $inicio = $ultimaPagada
            ? Carbon::parse($ultimaPagada->end_date)->addDay()->format('Y-m-d')
            : Carbon::now()->subDays(6)->format('Y-m-d');

        if ($inicio > $hoy) {
            $inicio = $hoy;
        }

        return [$inicio, $hoy];
    }

    /**
     * Calcula el sobre del ciclo abierto sin escribir nada en la base.
     * Es el mismo cálculo que ve el panel de autorización y la app móvil.
     */
    public function calcularCiclo(User $productor): array
    {
        [$inicio, $fin] = $this->rangoCicloAbierto($productor);
        $desde = min($inicio, $fin);
        $hasta = max($inicio, $fin);

        $precios = SystemPrice::current();
        $precioBase = (float) $precios->price_milk_base;

        $registros = CollectionRecord::where('producer_id', $productor->id)
            ->whereHas('route', fn ($q) => $q->whereBetween('date', [$desde, $hasta]))
            ->with(['route.zone', 'route.collector'])
            ->get()
            ->sortBy(fn ($r) => $r->route->date);

        $litros = (float) $registros->sum('liters');

        $infoPrecio = SystemPrice::getMilkPriceForProducer($productor->id, $desde, $hasta);
        $precioEfectivo = (float) $infoPrecio['price'];

        $peorAnalisis = LactoscanAnalysis::where('producer_id', $productor->id)
            ->whereBetween('analysis_date', [$desde, $hasta])
            ->orderByDesc('water_addition_percentage')
            ->first();

        $hayAdulteracion = $peorAnalisis && (float) $peorAnalisis->water_addition_percentage > 0;

        $penalidadPorLitro = $hayAdulteracion ? max(0, $precioBase - $precioEfectivo) : 0.0;
        $penalidadAgua = $hayAdulteracion ? round($penalidadPorLitro * $litros, 2) : 0.0;

        $deduccionesPendientes = ProducerDeduction::where('producer_id', $productor->id)
            ->where('status', 'pendiente')
            ->get();

        $deduccionesQueso = $deduccionesPendientes->filter(
            fn ($d) => str_contains(strtolower($d->concept), 'queso')
        );
        $totalQueso = round($deduccionesQueso->sum('amount'), 2);

        $bruto = round($litros * $precioBase, 2);
        $totalDeducciones = round($penalidadAgua + $totalQueso, 2);
        $neto = max(0, $bruto - $totalDeducciones);

        return [
            'producer' => $productor,
            'start_date' => $inicio,
            'end_date' => $fin,
            'records' => $registros,
            'liters' => $litros,
            'price_info' => $infoPrecio,
            'base_price' => $precioBase,
            'effective_price' => $precioEfectivo,
            'gross_base' => $bruto,
            'cheese_deductions' => $deduccionesQueso,
            'cheese_deductions_total' => $totalQueso,
            'adulteration_found' => $hayAdulteracion,
            'worst_analysis' => $peorAnalisis,
            'penalty_per_liter' => $penalidadPorLitro,
            'water_penalty_total' => $penalidadAgua,
            'total_deductions' => $totalDeducciones,
            'net' => $neto,
        ];
    }

    /** ¿Ya tiene un sobre autorizado esperando entrega en ruta? */
    public function sobreAutorizado(User $productor): ?ProducerSettlement
    {
        return ProducerSettlement::where('producer_id', $productor->id)
            ->where('status', 'autorizado')
            ->first();
    }

    /**
     * Administración autoriza el pago: crea la liquidación en estado
     * 'autorizado' y consolida las deducciones del ciclo.
     *
     * Devuelve null si el productor no tiene litros ni deducciones que liquidar.
     */
    public function autorizar(User $productor, User $autorizador, string $prefijo = 'LIQ-AUT'): ?ProducerSettlement
    {
        if ($this->sobreAutorizado($productor)) {
            throw new ReglaNegocioException(
                "{$productor->name} ya tiene un sobre autorizado pendiente de entrega en ruta.",
                'producer_id'
            );
        }

        $ciclo = $this->calcularCiclo($productor);

        if ($ciclo['liters'] <= 0 && $ciclo['total_deductions'] <= 0) {
            return null;
        }

        return DB::transaction(function () use ($ciclo, $productor, $autorizador, $prefijo) {
            $notaPenalidad = '';
            if ($ciclo['adulteration_found'] && $ciclo['water_penalty_total'] > 0) {
                $peor = $ciclo['worst_analysis'];
                $notaPenalidad = "Penalidad por leche adulterada ({$peor->water_addition_percentage}% agua detectada el {$peor->analysis_date}): "
                    . "descuento de S/ {$ciclo['penalty_per_liter']}/L aplicado a toda la semana (-S/ {$ciclo['water_penalty_total']}). ";
            }

            $liquidacion = ProducerSettlement::create([
                'settlement_code' => $prefijo . '-' . date('Ymd-His') . '-' . $productor->id,
                'producer_id' => $productor->id,
                'start_date' => $ciclo['start_date'],
                'end_date' => $ciclo['end_date'],
                'total_liters' => $ciclo['liters'],
                'price_per_liter' => $ciclo['effective_price'],
                'gross_total' => $ciclo['gross_base'],
                'deductions_total' => $ciclo['total_deductions'],
                'net_total' => $ciclo['net'],
                'status' => 'autorizado',
                'paid_at' => null,
                'paid_by' => null,
                'payment_method' => 'efectivo',
                'notes' => "Liquidación semanal autorizada por administración ({$autorizador->name}). "
                    . "Listo para armado y entrega de sobre en ruta. Subtotal base S/ {$ciclo['gross_base']}. "
                    . $notaPenalidad
                    . ($ciclo['cheese_deductions_total'] > 0 ? "Compras de queso a cuenta: S/ {$ciclo['cheese_deductions_total']}." : ''),
            ]);

            foreach ($ciclo['cheese_deductions'] as $deduccion) {
                $deduccion->update([
                    'status' => 'descontado',
                    'settlement_id' => $liquidacion->id,
                ]);
            }

            if ($ciclo['water_penalty_total'] > 0) {
                $peor = $ciclo['worst_analysis'];
                ProducerDeduction::create([
                    'producer_id' => $productor->id,
                    'settlement_id' => $liquidacion->id,
                    'date' => Carbon::today()->format('Y-m-d'),
                    'concept' => "Penalidad semanal por leche adulterada ({$peor->water_addition_percentage}% agua)",
                    'amount' => $ciclo['water_penalty_total'],
                    'status' => 'descontado',
                    'created_by' => $autorizador->id,
                    'notes' => "Aplicado a los {$ciclo['liters']} litros del ciclo conforme a la regla de Huata.",
                ]);
            }

            return $liquidacion;
        });
    }

    /**
     * El pagador de campo entrega el sobre en efectivo durante la ruta.
     * Solo puede entregar sobres que administración ya autorizó.
     */
    public function entregarSobre(User $productor, User $pagador): ProducerSettlement
    {
        $liquidacion = $this->sobreAutorizado($productor);

        if (!$liquidacion) {
            throw new ReglaNegocioException(
                "No se puede entregar el sobre: el pago de {$productor->name} aún no ha sido autorizado por la Administración.",
                'producer_id'
            );
        }

        $liquidacion->update([
            'status' => 'pagado',
            'paid_at' => Carbon::now(),
            'paid_by' => $pagador->id,
            'payment_method' => 'efectivo',
            'notes' => ($liquidacion->notes ? $liquidacion->notes . ' | ' : '')
                . 'Sobre de S/ ' . number_format($liquidacion->net_total, 2)
                . " entregado en efectivo en ruta del viernes por {$pagador->name}.",
        ]);

        return $liquidacion;
    }
}
