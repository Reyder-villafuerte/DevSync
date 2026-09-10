<?php

namespace App\Services\Liquidacion;

use App\Enums\EstadoSemanaPago;
use App\Exceptions\ReglaNegocioException;
use App\Models\DetalleLiquidacion;
use App\Models\Liquidacion;
use App\Models\PrecioCompraLeche;
use App\Models\RegistroAcopio;
use App\Models\Sancion;
use App\Models\SemanaPago;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: generación de la liquidación semanal (ciclo jueves→miércoles,
 * se paga el viernes con sobre físico).
 *
 * Congela (requisito técnico 4) todo lo que podría cambiar después:
 *  - el precio de compra de leche vigente en esa semana,
 *  - el monto de cada descuento por sanción.
 * Aplica las sanciones VIGENTES pendientes de liquidar:
 *  - RN-05 <5% (1ra o reincidente): descuento porcentual sobre el bruto.
 *  - RN-05 ≥5% (expulsión): toda la semana se paga a tarifa mínima.
 *
 * Todo en una transacción (restricción: escrituras que afectan liquidaciones).
 */
class LiquidacionService
{
    public function generarSemana(SemanaPago $semana): Collection
    {
        if (in_array($semana->estado, [EstadoSemanaPago::PAGADA, EstadoSemanaPago::CERRADA], true)) {
            throw new ReglaNegocioException("La semana ya está {$semana->estado->value}.", 'SEMANA_CERRADA');
        }

        return DB::transaction(function () use ($semana) {
            $semana->forceFill(['estado' => EstadoSemanaPago::EN_CALCULO->value])->save();

            // --- Tarifa congelada de la semana ---
            $precio = PrecioCompraLeche::query()
                ->vigenteEn($semana->fecha_fin->toDateString())
                ->orderByDesc('vigente_desde')
                ->first();

            if (! $precio) {
                throw new ReglaNegocioException(
                    'No hay tarifa de compra de leche vigente para la semana.',
                    'SIN_TARIFA_VIGENTE',
                    ['fecha' => $semana->fecha_fin->toDateString()],
                );
            }

            // --- Litros por productor en la ventana de la semana ---
            $litrosPorProductor = RegistroAcopio::query()
                ->where('deleted', false)
                ->enSemana($semana->fecha_inicio->toDateString(), $semana->fecha_fin->toDateString())
                ->selectRaw('productor_id, SUM(litros) AS litros')
                ->groupBy('productor_id')
                ->pluck('litros', 'productor_id');

            // --- Sanciones a considerar: las pendientes + las ya aplicadas a
            // ESTA semana (para que re-generar la semana sea idempotente). ---
            $sancionesPorProductor = Sancion::query()
                ->where('estado', '!=', 'anulada')
                ->where(fn ($q) => $q
                    ->where('aplicada_en_liquidacion', false)
                    ->orWhere('semana_pago_id', $semana->id))
                ->with('productor')
                ->get()
                ->groupBy('productor_id');

            $productoresAfectados = collect($litrosPorProductor->keys())
                ->merge($sancionesPorProductor->keys())
                ->unique();

            $liquidaciones = collect();

            foreach ($productoresAfectados as $productorId) {
                $litros = round((float) ($litrosPorProductor[$productorId] ?? 0), 2);
                $sanciones = $sancionesPorProductor->get($productorId, collect());

                $liquidaciones->push(
                    $this->liquidarProductor($semana, $productorId, $litros, $precio, $sanciones)
                );
            }

            $semana->forceFill([
                'estado' => EstadoSemanaPago::LIQUIDADA->value,
                'precio_compra_leche_id' => $precio->id,
                'liquidada_en' => now(),
            ])->save();

            return $liquidaciones;
        });
    }

    private function liquidarProductor(
        SemanaPago $semana,
        string $productorId,
        float $litros,
        PrecioCompraLeche $precio,
        Collection $sanciones,
    ): Liquidacion {
        // ¿Hay expulsión por agua (RN-05 ≥5%)? => toda la semana a tarifa mínima.
        $expulsion = $sanciones->first(fn (Sancion $s) => $s->expulsa);
        $tarifaDegradada = $expulsion !== null;
        $precioLitro = $tarifaDegradada
            ? (float) ($expulsion->tarifa_degradada_litro ?? $precio->precio_litro_minimo)
            : (float) $precio->precio_litro;

        $bruto = round($litros * $precioLitro, 2);

        $liquidacion = Liquidacion::updateOrCreate(
            ['semana_pago_id' => $semana->id, 'productor_id' => $productorId],
            [
                'litros_totales' => $litros,
                'precio_litro_aplicado' => $precioLitro,
                'tarifa_degradada' => $tarifaDegradada,
                'monto_bruto' => $bruto,
                'total_descuentos' => 0,
                'monto_neto' => $bruto,
                'estado' => 'calculada',
            ],
        );

        // Renglones: reemplazamos el detalle en cada recálculo.
        $liquidacion->detalles()->delete();
        DetalleLiquidacion::create([
            'liquidacion_id' => $liquidacion->id,
            'concepto' => 'ingreso_leche',
            'descripcion' => "Leche acopiada: {$litros} L × S/ ".number_format($precioLitro, 4),
            'referencia_id' => null,
            'monto' => $bruto,
        ]);

        // --- Descuentos por sanción ---
        $totalDescuentos = 0.0;
        foreach ($sanciones as $sancion) {
            /** @var Sancion $sancion */
            if ($sancion->expulsa) {
                // La expulsión no genera "descuento": ya se pagó a tarifa mínima.
                // Se marca aplicada para no arrastrarla.
                $this->marcarAplicada($sancion, $semana, montoDescuento: 0);

                continue;
            }

            if (! $sancion->tipo->generaDescuento()) {
                $this->marcarAplicada($sancion, $semana, montoDescuento: 0);

                continue;
            }

            $pct = (float) ($sancion->porcentaje_descuento ?? 0);
            $monto = $sancion->monto_descuento !== null
                ? (float) $sancion->monto_descuento
                : round($bruto * $pct, 2);

            // No dejamos la liquidación en negativo: se descuenta hasta el bruto
            // disponible y el remanente queda como sanción no aplicada.
            $disponible = round($bruto - $totalDescuentos, 2);
            $montoAplicado = min($monto, max($disponible, 0));

            if ($montoAplicado > 0) {
                DetalleLiquidacion::create([
                    'liquidacion_id' => $liquidacion->id,
                    'concepto' => 'descuento_agua',
                    'descripcion' => "Descuento por adulteración con agua ({$sancion->tipo->value})"
                        .($montoAplicado < $monto ? ' — parcial, remanente no aplicado' : ''),
                    'referencia_id' => $sancion->id,
                    'monto' => -$montoAplicado,
                ]);
                $totalDescuentos = round($totalDescuentos + $montoAplicado, 2);
            }

            $this->marcarAplicada($sancion, $semana, montoDescuento: $montoAplicado);
        }

        $neto = round($bruto - $totalDescuentos, 2);
        $liquidacion->forceFill([
            'total_descuentos' => $totalDescuentos,
            'monto_neto' => $neto,
        ])->save();

        return $liquidacion->fresh('detalles');
    }

    /** Registra el pago del sobre de una liquidación (paso del viernes). */
    public function marcarPagada(Liquidacion $liquidacion): void
    {
        if ($liquidacion->estado === 'anulada') {
            throw new ReglaNegocioException('La liquidación está anulada.', 'LIQUIDACION_ANULADA');
        }
        if (in_array($liquidacion->estado, ['pagada', 'entregada'], true)) {
            return;
        }

        $liquidacion->forceFill(['estado' => 'pagada'])->save();
    }

    /** Marca la entrega física del sobre al productor. */
    public function marcarSobreEntregado(Liquidacion $liquidacion): void
    {
        if (! in_array($liquidacion->estado, ['pagada', 'entregada'], true)) {
            throw new ReglaNegocioException('Primero registre el pago del sobre.', 'PAGO_PENDIENTE');
        }

        $liquidacion->forceFill([
            'estado' => 'entregada',
            'sobre_entregado_en' => now(),
        ])->save();
    }

    private function marcarAplicada(Sancion $sancion, SemanaPago $semana, float $montoDescuento): void
    {
        $sancion->forceFill([
            'semana_pago_id' => $semana->id,
            'monto_descuento' => $montoDescuento,
            'aplicada_en_liquidacion' => true,
            'estado' => 'aplicada',
        ])->save();
    }
}
