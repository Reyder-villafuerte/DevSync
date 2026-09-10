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
 *   con signo. La cantidad "actual" se lee de la vista materializada
 *   stock_actual (o de la suma del libro).
 * - Toda escritura de stock va en transacción (restricción del enunciado).
 * - El REFRESH de la vista materializada CONCURRENTLY no puede correr dentro
 *   de una transacción, así que se difiere con DB::afterCommit().
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
                // sobre un SUM en PostgreSQL).
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

            DB::afterCommit(fn () => $this->refrescarVistaStock());

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

    /** Refresco de la vista materializada. CONCURRENTLY para no bloquear lecturas. */
    public function refrescarVistaStock(): void
    {
        // CONCURRENTLY exige NO estar en transacción; si lo estamos (p. ej.
        // dentro de otro Service o de las pruebas), se cae al refresh normal.
        if (DB::transactionLevel() > 0) {
            DB::statement('REFRESH MATERIALIZED VIEW stock_actual');

            return;
        }

        DB::statement('REFRESH MATERIALIZED VIEW CONCURRENTLY stock_actual');
    }
}
