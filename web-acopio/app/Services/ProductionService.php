<?php

namespace App\Services;

use App\Models\Entrega;
use App\Models\LoteProduccion;
use App\Models\StockMovimiento;
use App\Models\StockQueso;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionService
{
    public function create(array $data, User $actor): LoteProduccion
    {
        abort_unless($actor->hasPermission('production.manage'), 403);

        return DB::transaction(function () use ($data, $actor) {
            $deliveries = Entrega::where('estado', 'CONFORME')->whereColumn('litros', '>', 'litros_utilizados')->orderBy('id')->lockForUpdate()->get();
            $available = $deliveries->sum(fn ($e) => (float) $e->litros - (float) $e->litros_utilizados);
            if ((float) $data['litros_leche'] > $available) {
                throw ValidationException::withMessages(['litros_leche' => 'Leche conforme disponible: '.number_format($available, 3).' L. La leche rechazada u observada no puede usarse.']);
            }
            $lote = LoteProduccion::create(collect($data)->only(['codigo_lote', 'tipo_producto', 'litros_leche', 'moldes_obtenidos', 'observaciones'])->all() + ['rendimiento' => $data['moldes_obtenidos'] / $data['litros_leche'] * 100, 'fecha' => now(), 'usuario_id' => $actor->id, 'estado' => 'TERMINADO']);
            $remaining = (float) $data['litros_leche'];
            foreach ($deliveries as $delivery) {
                if ($remaining <= 0) {
                    break;
                } $used = min($remaining, (float) $delivery->litros - (float) $delivery->litros_utilizados);
                $lote->entregas()->attach($delivery->id, ['litros' => $used]);
                $delivery->increment('litros_utilizados', $used);
                $remaining -= $used;
            }
            if ($data['tipo_producto'] !== 'Yogurt') {
                $stock = StockQueso::where('tipo_producto', $data['tipo_producto'])->lockForUpdate()->firstOrFail();
                $stock->increment('cantidad', $data['moldes_obtenidos']);
                StockMovimiento::create(['stock_queso_id' => $stock->id, 'cantidad' => $data['moldes_obtenidos'], 'motivo' => 'PRODUCCION', 'lote_id' => $lote->id, 'usuario_id' => $actor->id]);
            }
            Audit::record('produccion.registrada', $lote);

            return $lote;
        });
    }
}
