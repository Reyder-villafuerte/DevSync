<?php

namespace App\Services\Produccion;

use App\Models\InventoryStock;
use App\Models\ProductionOrder;
use App\Models\Purchase;
use App\Models\Supply;
use App\Models\SupplyMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Único punto por donde se mueve el stock de un insumo.
 *
 * Toda entrada o salida queda con su motivo y el saldo que dejó, de modo que el
 * almacén siempre pueda explicar en qué se fue la leche o la fruta.
 */
class AlmacenService
{
    /**
     * Aplica el movimiento y devuelve el saldo resultante.
     *
     * @param  float  $delta  positivo = entrada, negativo = salida
     * @param  float|null  $costoUnitario  precio al que entró, si el motivo es una compra
     */
    public function mover(
        Supply $insumo,
        float $delta,
        string $tipo,
        ?ProductionOrder $orden = null,
        ?User $usuario = null,
        ?string $notas = null,
        ?Purchase $compra = null,
        ?float $costoUnitario = null
    ): float {
        return DB::transaction(function () use ($insumo, $delta, $tipo, $orden, $usuario, $notas, $compra, $costoUnitario) {
            $stock = InventoryStock::adjustStock($insumo->item_code, $delta, $insumo->name, $insumo->unit)
                ->vincularInsumo($insumo);

            SupplyMovement::create([
                'supply_id' => $insumo->id,
                'type' => in_array($tipo, SupplyMovement::TIPOS, true) ? $tipo : 'ajuste',
                'quantity' => $delta,
                'unit_cost' => $costoUnitario,
                'balance_after' => (float) $stock->current_stock,
                'production_order_id' => $orden?->id,
                'purchase_id' => $compra?->id,
                'registered_by' => $usuario?->id,
                'notes' => $notas,
            ]);

            return (float) $stock->current_stock;
        });
    }
}
