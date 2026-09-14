<?php

namespace App\Services\Ventas;

use App\Exceptions\ReglaNegocioException;
use App\Models\Customer;
use App\Models\DailyCashClosure;
use App\Models\InventoryStock;
use App\Models\ProducerDeduction;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Venta de queso en mostrador y arqueo/cierre de caja.
 *
 * Dos formas de pago: efectivo (dinero físico) y descuento_leche (a cuenta de
 * la leche del proveedor, que genera una deducción en su liquidación semanal).
 */
class VentaService
{
    /**
     * Registra una venta, descuenta el stock de moldes y, si se paga a cuenta
     * de leche, deja la deducción pendiente del proveedor.
     */
    public function registrarVenta(
        User $vendedor,
        array $datos,
        ?string $clientUuid = null
    ): Sale {
        $cantidad = (int) ($datos['cheese_molds_quantity'] ?? 0);

        if ($cantidad < 1) {
            throw new ReglaNegocioException('La cantidad de moldes debe ser al menos 1.', 'cheese_molds_quantity');
        }

        $stockQueso = InventoryStock::getStock('CHEESE_MOLD_UNITS');
        if ($stockQueso < $cantidad) {
            throw new ReglaNegocioException(
                "Stock insuficiente de quesos. Disponibles: {$stockQueso} moldes.",
                'cheese_molds_quantity'
            );
        }

        return DB::transaction(function () use ($vendedor, $datos, $cantidad, $clientUuid) {
            $cliente = $this->resolverCliente($datos, $cantidad);

            $precioUnitario = $cliente->determineUnitPrice($cantidad);
            $total = $precioUnitario * $cantidad;
            $formaPago = $datos['payment_method'] ?? 'efectivo';

            $venta = Sale::create([
                'client_uuid' => $clientUuid,
                'receipt_number' => Sale::generateReceiptNumber(),
                'customer_id' => $cliente->id,
                'seller_id' => $vendedor->id,
                'cheese_molds_quantity' => $cantidad,
                'unit_price' => $precioUnitario,
                'total_amount' => $total,
                'payment_method' => $formaPago,
                'sold_at' => $datos['sold_at'] ?? now(),
            ]);

            if ($formaPago === 'descuento_leche' && $cliente->linked_user_id) {
                ProducerDeduction::create([
                    'producer_id' => $cliente->linked_user_id,
                    'sale_id' => $venta->id,
                    'date' => now()->format('Y-m-d'),
                    'concept' => "Compra de {$cantidad} moldes de queso a cuenta de leche (Recibo #{$venta->receipt_number})",
                    'amount' => $total,
                    'status' => 'pendiente',
                    'created_by' => $vendedor->id,
                    'notes' => 'Queso despachado en planta a descontar en la liquidación semanal de leche.',
                ]);
            }

            InventoryStock::adjustStock('CHEESE_MOLD_UNITS', -$cantidad);

            return $venta;
        });
    }

    /** Cliente ya registrado o alta rápida en el mostrador. */
    private function resolverCliente(array $datos, int $cantidad): Customer
    {
        if (!empty($datos['customer_id'])) {
            $cliente = Customer::find($datos['customer_id']);
            if ($cliente) {
                return $cliente;
            }
        }

        if (!empty($datos['customer_client_uuid'])) {
            $cliente = Customer::where('client_uuid', $datos['customer_client_uuid'])->first();
            if ($cliente) {
                return $cliente;
            }
        }

        if (empty($datos['new_last_name'])) {
            throw new ReglaNegocioException('Debe elegir un cliente o registrar uno nuevo.', 'customer_id');
        }

        $tipo = $datos['new_type'] ?? null;

        return Customer::create([
            'client_uuid' => $datos['customer_client_uuid'] ?? null,
            'first_name' => $datos['new_first_name'] ?? '',
            'last_name' => $datos['new_last_name'],
            'dni_ruc' => $datos['new_dni_ruc'] ?? null,
            'phone' => $datos['new_phone'] ?? null,
            'type' => $tipo ?: ($cantidad >= 10 ? 'mayorista' : 'local'),
            'is_wholesale_approved' => $tipo === 'mayorista',
        ]);
    }

    /**
     * Cierre de caja del día: concilia todas las ventas sin cierre bajo un
     * arqueo y deja la bandeja de "ventas de hoy" en blanco.
     */
    public function cerrarCaja(User $cajero, ?string $notas = null, ?string $fecha = null, ?string $clientUuid = null): DailyCashClosure
    {
        $fecha = $fecha ?: date('Y-m-d');

        $pendientes = Sale::whereDate('sold_at', $fecha)->whereNull('closure_id')->get();

        if ($pendientes->isEmpty()) {
            throw new ReglaNegocioException(
                'No hay ventas activas pendientes de cierre para el día de hoy.',
                'cierre'
            );
        }

        return DB::transaction(function () use ($cajero, $notas, $fecha, $clientUuid, $pendientes) {
            $cierre = DailyCashClosure::create([
                'client_uuid' => $clientUuid,
                'date' => $fecha,
                'closed_by' => $cajero->id,
                'total_cash' => $pendientes->where('payment_method', 'efectivo')->sum('total_amount'),
                'total_milk_discount' => $pendientes->where('payment_method', 'descuento_leche')->sum('total_amount'),
                'total_amount' => $pendientes->sum('total_amount'),
                'cheese_molds_quantity' => $pendientes->sum('cheese_molds_quantity'),
                'sales_count' => $pendientes->count(),
                'notes' => $notas ?: 'Cierre de caja y arqueo conforme.',
                'closed_at' => now(),
            ]);

            Sale::whereDate('sold_at', $fecha)
                ->whereNull('closure_id')
                ->update(['closure_id' => $cierre->id]);

            return $cierre;
        });
    }
}
