<?php

namespace App\Services;

use App\Enums\TipoCliente;
use App\Models\StockMovimiento;
use App\Models\StockQueso;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesService
{
    public function create(array $data, User $actor): Venta
    {
        abort_unless($actor->hasPermission('sales.manage'), 403);

        return DB::transaction(function () use ($data, $actor) {
            $stock = StockQueso::lockForUpdate()->findOrFail($data['stock_queso_id']);
            if (Venta::where('uuid', $data['uuid'])->exists()) {
                throw ValidationException::withMessages(['uuid' => 'Esta venta ya fue registrada.']);
            }
            if ((int) $data['cantidad'] > $stock->cantidad) {
                throw ValidationException::withMessages(['cantidad' => 'Stock insuficiente. Disponible: '.$stock->cantidad]);
            }
            $price = TipoCliente::from($data['tipo_cliente'])->precio();
            $sale = Venta::create(['uuid' => $data['uuid'], 'cliente' => $data['cliente'], 'tipo_cliente' => $data['tipo_cliente'], 'cantidad' => $data['cantidad'], 'precio_unitario' => $price, 'total' => $price * $data['cantidad'], 'fecha' => now(), 'usuario_id' => $actor->id, 'estado' => 'CONFIRMADA']);
            $sale->detalles()->create(['stock_queso_id' => $stock->id, 'cantidad' => $data['cantidad'], 'precio_unitario' => $price, 'subtotal' => $sale->total]);
            $stock->decrement('cantidad', $data['cantidad']);
            StockMovimiento::create(['stock_queso_id' => $stock->id, 'cantidad' => -$data['cantidad'], 'motivo' => 'VENTA', 'venta_id' => $sale->id, 'usuario_id' => $actor->id]);
            Audit::record('venta.registrada', $sale);

            return $sale;
        });
    }
}
