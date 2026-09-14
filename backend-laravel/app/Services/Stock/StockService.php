<?php

namespace App\Services\Stock;

use App\Enums\TipoMovimientoStock;
use App\Exceptions\ReglaNegocioException;
use App\Models\MovimientoStock;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: escritura en el LIBRO DE EVENTOS de stock (requisito técnico 3).
 *
 * - El stock NUNCA es un contador: cada cambio es un movimiento con cantidad
 *   con signo. La cantidad "actual" se lee de la vista normal
 *   stock_actual (o de la suma del libro).
 * - Toda escritura de stock va en transacción (restricción del enunciado).
 * - La vista de MySQL se calcula al leer y no necesita refresco manual.
 */
class StockService
{
    /**
     * Registra un movimiento. Recibe la cantidad en VALOR ABSOLUTO; el signo
     * lo pone el tipo de movimiento.
     */
    public function registrarMovimiento(
        Producto $producto,
        TipoMovimientoStock $tipo,
        float $cantidadAbsoluta,
        ?Model $origen = null,
        ?string $usuarioId = null,
        ?string $motivo = null,
    ): MovimientoStock {
        if ($cantidadAbsoluta <= 0) {
            throw new ReglaNegocioException('La cantidad del movimiento debe ser mayor a cero.', 'STOCK_CANTIDAD_INVALIDA');
        }

        $cantidad = round($cantidadAbsoluta, 2) * $tipo->signo();

        return DB::transaction(function () use ($producto, $tipo, $cantidad, $origen, $usuarioId, $motivo) {
            if ($tipo->signo() < 0 && ! config('milkflow.stock.permitir_negativo')) {
                // Se serializa contra egresos concurrentes del MISMO producto
                // bloqueando su fila de catálogo (no se puede hacer FOR UPDATE
                // sobre una agregación SUM).
                Producto::query()->whereKey($producto->id)->lockForUpdate()->first();
                $this->asegurarDisponibilidad($producto, abs($cantidad));
            }

            $movimiento = MovimientoStock::create([
                'producto_id' => $producto->id,
                'tipo_movimiento' => $tipo->value,
                'cantidad' => $cantidad,
                'origen_tipo' => $origen?->getMorphClass(),
                'origen_id' => $origen?->getKey(),
                'registrado_por' => $usuarioId ?? auth()->id(),
                'ocurrido_en' => now(),
                'motivo' => $motivo,
            ]);

            return $movimiento;
        });
    }

    /**
     * Valida que haya stock para un egreso de $cantidad (valor absoluto).
     * Lanza ReglaNegocioException con código `stock_insuficiente` si no alcanza.
     * La usa el flujo de venta ANTES de construir el comprobante.
     */
    public function asegurarDisponibilidad(Producto $producto, float $cantidad): void
    {
        if (config('milkflow.stock.permitir_negativo')) {
            return;
        }
        $disponible = $this->cantidadActual($producto->id);
        if ($disponible < round($cantidad, 2)) {
            throw new ReglaNegocioException(
                "Stock insuficiente de {$producto->nombre}: disponible {$disponible}, se requieren ".round($cantidad, 2).'.',
                'stock_insuficiente',
                ['productoId' => $producto->id, 'disponible' => $disponible, 'requerido' => round($cantidad, 2)],
            );
        }
    }

    /** Cantidad actual = suma del libro de eventos para el producto. */
    public function cantidadActual(string $productoId): float
    {
        return (float) MovimientoStock::query()
            ->where('producto_id', $productoId)
            ->where('deleted', false)
            ->sum('cantidad');
    }

    /**
     * Compatibilidad con llamadas existentes. La vista normal de MySQL está
     * siempre actualizada, por lo que no existe una operación de refresco.
     */
    public function refrescarVistaStock(): void
    {
        // No-op intencional.
    }
}
