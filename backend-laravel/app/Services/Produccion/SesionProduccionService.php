<?php

namespace App\Services\Produccion;

use App\Enums\EstadoSesionProduccion;
use App\Enums\TipoMovimientoStock;
use App\Exceptions\ReglaNegocioException;
use App\Models\Producto;
use App\Models\SesionProduccion;
use App\Models\Usuario;
use App\Services\Stock\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: ciclo de una sesión de producción quesera.
 *
 * Al INICIAR: se crea el lote con un código correlativo del día y queda
 * "en proceso".
 * Al COMPLETAR una sesión:
 *  1. se calcula y persiste el rendimiento (RN-08),
 *  2. se ingresa el producto terminado al stock como un movimiento
 *     produccion_ingreso.
 *
 * Todo en una transacción (restricción: escrituras que afectan stock).
 */
class SesionProduccionService
{
    public function __construct(
        private readonly RendimientoService $rendimiento,
        private readonly StockService $stock,
    ) {}

    /**
     * Estimación de unidades a obtener con $litros según el rendimiento esperado
     * del producto (punto medio del rango meta). Cálculo puro: no persiste.
     * Devuelve null si el producto no controla rendimiento (p. ej. leche o yogur).
     */
    public function estimarUnidades(Producto $producto, float $litros): ?int
    {
        if (! $producto->controla_rendimiento || $litros <= 0) {
            return null;
        }

        $min = (float) ($producto->rendimiento_min_por_100l ?? config('milkflow.rendimiento.min_por_100l'));
        $max = (float) ($producto->rendimiento_max_por_100l ?? config('milkflow.rendimiento.max_por_100l'));
        $promedio = ($min + $max) / 2;

        return (int) round($litros / 100 * $promedio);
    }

    /** Abre una sesión de producción: genera el código de lote y la deja en proceso. */
    public function iniciar(Producto $producto, float $litros, Usuario $jefe): SesionProduccion
    {
        if ($litros <= 0) {
            throw new ReglaNegocioException('Los litros a procesar deben ser mayores a cero.', 'PRODUCCION_LITROS_INVALIDOS');
        }

        return DB::transaction(function () use ($producto, $litros, $jefe) {
            return SesionProduccion::create([
                'producto_id' => $producto->id,
                'jefe_produccion_id' => $jefe->id,
                'lote_codigo' => $this->siguienteLoteCodigo(),
                'fecha' => now()->toDateString(),
                'litros_procesados' => round($litros, 2),
                'estado' => EstadoSesionProduccion::EN_PROCESO->value,
            ]);
        });
    }

    /** Código de lote del día: L-AAAAMMDD-NNN (contador de sesiones creadas hoy). */
    private function siguienteLoteCodigo(): string
    {
        $hoy = now();
        $correlativo = SesionProduccion::query()
            ->whereDate('created_at', $hoy->toDateString())
            ->count() + 1;

        return sprintf('L-%s-%03d', $hoy->format('Ymd'), $correlativo);
    }

    public function completar(SesionProduccion $sesion, int $unidadesProducidas, ?float $litrosReales = null): SesionProduccion
    {
        if (in_array($sesion->estado, [EstadoSesionProduccion::COMPLETADA, EstadoSesionProduccion::ANULADA], true)) {
            throw new ReglaNegocioException(
                "La sesión {$sesion->lote_codigo} ya está {$sesion->estado->value}.",
                'SESION_ESTADO_INVALIDO',
            );
        }
        if ($unidadesProducidas < 0) {
            throw new ReglaNegocioException('Las unidades producidas no pueden ser negativas.', 'PRODUCCION_UNIDADES_INVALIDAS');
        }

        return DB::transaction(function () use ($sesion, $unidadesProducidas, $litrosReales) {
            if ($litrosReales !== null) {
                $sesion->litros_procesados = round($litrosReales, 2);
            }
            $sesion->unidades_producidas = $unidadesProducidas;

            // --- RN-08 ---
            $r = $this->rendimiento->calcular($sesion, $unidadesProducidas);
            $sesion->rendimiento_por_100l = $r['rendimiento'];
            $sesion->cumple_rn08 = $r['cumple'];
            $sesion->rendimiento_detalle = $r['detalle'];

            if (! $r['cumple'] && config('milkflow.rendimiento.bloquea_cierre_si_incumple')) {
                // Decisión: por defecto NO se bloquea (la producción física ya
                // ocurrió); solo se activa este guard si la planta lo exige.
                throw new ReglaNegocioException($r['detalle'], 'RN08_INCUMPLIDO', $r);
            }

            $sesion->estado = EstadoSesionProduccion::COMPLETADA;
            $sesion->completada_en = now();
            $sesion->save();

            if ($unidadesProducidas > 0) {
                $this->stock->registrarMovimiento(
                    producto: $sesion->producto,
                    tipo: TipoMovimientoStock::PRODUCCION_INGRESO,
                    cantidadAbsoluta: $unidadesProducidas,
                    origen: $sesion,
                    usuarioId: $sesion->jefe_produccion_id,
                    motivo: "Producción lote {$sesion->lote_codigo}",
                );
            }

            return $sesion->fresh(['producto', 'movimientosStock']);
        });
    }
}
