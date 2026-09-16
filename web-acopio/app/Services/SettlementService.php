<?php

namespace App\Services;

use App\Models\Liquidacion;
use App\Models\Productor;
use App\Models\Sancion;
use App\Models\User;
use App\Notifications\MilkFlowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SettlementService
{
    public function calculate(int $producerId, string $date, User $actor): Liquidacion
    {
        abort_unless($actor->hasPermission('settlements.manage'), 403);

        return DB::transaction(function () use ($producerId, $date, $actor) {
            $producer = Productor::lockForUpdate()->findOrFail($producerId);
            [$start,$end] = BusinessWeek::bounds($date);
            if ($end->isFuture()) {
                throw ValidationException::withMessages(['periodo' => 'Solo se pueden liquidar semanas cerradas (jueves a miércoles).']);
            }
            if (Liquidacion::where('productor_id', $producerId)->whereDate('periodo_inicio', $start)->exists()) {
                throw ValidationException::withMessages(['periodo' => 'Este periodo ya fue liquidado.']);
            }
            $all = $producer->entregas()->whereBetween('fecha_hora', [$start, $end])->lockForUpdate()->get();
            if ($all->contains('estado', 'PENDIENTE')) {
                throw ValidationException::withMessages(['periodo' => 'Hay entregas pendientes de calidad en este periodo.']);
            }
            $deliveries = $all->whereIn('estado', ['CONFORME', 'OBSERVADA']);
            $liters = $deliveries->sum('litros');
            if ($liters <= 0) {
                throw ValidationException::withMessages(['periodo' => 'No hay litros liquidables en este periodo.']);
            }
            $sanctions = Sancion::where('productor_id', $producerId)->whereDate('periodo_inicio', $start)->get();
            $normal = (float) Settings::get('tarifa_normal', '1.70');
            $rate = $sanctions->isEmpty() ? $normal : min($normal, (float) $sanctions->min('tarifa'));
            $discount = (float) $sanctions->sum('descuento');
            $subtotal = round($liters * $normal, 2);
            $penalty = round($liters * ($normal - $rate), 2);
            $settlement = Liquidacion::create(['productor_id' => $producerId, 'periodo_inicio' => $start->toDateString(), 'periodo_fin' => $end->toDateString(), 'litros' => $liters, 'tarifa' => $rate, 'subtotal' => $subtotal, 'descuentos' => $discount, 'penalizaciones' => $penalty, 'total' => max(0, $subtotal - $discount - $penalty), 'estado' => 'Calculada', 'usuario_id' => $actor->id]);
            foreach ($deliveries as $d) {
                $settlement->detalles()->create(['entrega_id' => $d->id, 'litros' => $d->litros, 'tarifa' => $rate, 'subtotal' => round($d->litros * $rate, 2)]);
            }
            Audit::record('liquidacion.calculada', $settlement);
            $producer->user?->notify(new MilkFlowNotification('Liquidación calculada', 'Periodo '.$start->format('d/m/Y').' al '.$end->format('d/m/Y').'. Total S/ '.$settlement->total));

            return $settlement;
        });
    }
}
