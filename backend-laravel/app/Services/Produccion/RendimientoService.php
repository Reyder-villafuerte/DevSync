<?php

namespace App\Services\Produccion;

use App\Models\SesionProduccion;
use Illuminate\Support\Collection;

/**
 * Use Case: cálculo del rendimiento quesero (RN-08).
 *
 * Meta estricta: 11 a 12 quesos por cada 100 litros procesados. El rango real
 * se toma del producto (rendimiento_min/max_por_100l); si no está definido, se
 * usan los valores de config/milkflow.php.
 *
 * El resultado se PERSISTE en la sesión al completarla y no se recalcula
 * después (coherente con el criterio de "congelar" veredictos).
 */
class RendimientoService
{
    /**
     * @return array{rendimiento: float, cumple: bool, detalle: string, min: float, max: float}
     */
    public function calcular(SesionProduccion $sesion, ?int $unidades = null): array
    {
        $sesion->loadMissing('producto');
        $unidades ??= (int) $sesion->unidades_producidas;
        $litros = (float) $sesion->litros_procesados;

        if ($litros <= 0) {
            return ['rendimiento' => 0.0, 'cumple' => false, 'detalle' => 'Litros procesados inválidos.', 'min' => 0, 'max' => 0];
        }

        $min = (float) ($sesion->producto->rendimiento_min_por_100l ?? config('milkflow.rendimiento.min_por_100l'));
        $max = (float) ($sesion->producto->rendimiento_max_por_100l ?? config('milkflow.rendimiento.max_por_100l'));

        $rendimiento = round($unidades / $litros * 100, 2);
        $cumple = $rendimiento >= $min && $rendimiento <= $max;

        $detalle = $cumple
            ? "Rendimiento {$rendimiento}/100 L dentro de la meta [{$min}, {$max}]."
            : ($rendimiento < $min
                ? "Rendimiento {$rendimiento}/100 L por DEBAJO de la meta mínima {$min}."
                : "Rendimiento {$rendimiento}/100 L por ENCIMA de la meta máxima {$max} (posible sobre-declaración o merma no registrada).");

        return compact('rendimiento', 'cumple', 'detalle', 'min', 'max');
    }

    /**
     * Semáforo RN-08 agregado sobre las sesiones ya COMPLETADAS: junta litros y
     * unidades de los productos que controlan rendimiento y devuelve el estado
     * global frente a la meta de 11 a 12 quesos por 100 litros.
     *
     * @param  Collection<int,SesionProduccion>  $sesionesCompletadas
     * @return array{rendimiento: float, estado: string, min: float, max: float, litros: float, unidades: int}
     */
    public function semaforo(Collection $sesionesCompletadas): array
    {
        $min = (float) config('milkflow.rendimiento.min_por_100l');
        $max = (float) config('milkflow.rendimiento.max_por_100l');

        $relevantes = $sesionesCompletadas->filter(
            fn (SesionProduccion $s) => $s->loadMissing('producto')->producto?->controla_rendimiento,
        );

        $litros = round((float) $relevantes->sum(fn (SesionProduccion $s) => (float) $s->litros_procesados), 2);
        $unidades = (int) $relevantes->sum(fn (SesionProduccion $s) => (int) $s->unidades_producidas);

        if ($litros <= 0) {
            return ['rendimiento' => 0.0, 'estado' => 'sin_datos', 'min' => $min, 'max' => $max, 'litros' => 0.0, 'unidades' => 0];
        }

        $rendimiento = round($unidades / $litros * 100, 2);
        $estado = $rendimiento < $min ? 'bajo' : ($rendimiento > $max ? 'sobre' : 'dentro');

        return compact('rendimiento', 'estado', 'min', 'max', 'litros', 'unidades');
    }
}
