<?php

namespace App\Services\Acopio;

use App\Exceptions\ReglaNegocioException;
use App\Models\Conciliacion;
use App\Models\RutaAcopio;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: conciliación en planta del volumen declarado por el acopiador
 * contra el caudalímetro.
 *
 * La diferencia (litros y %) y la marca de alerta se CALCULAN aquí y se
 * PERSISTEN; nadie las recalcula al leer, porque la tolerancia aplicada
 * podría cambiar en el futuro.
 */
class ConciliacionService
{
    public function conciliar(
        RutaAcopio $rutaAcopio,
        float $litrosCaudalimetro,
        Usuario $registrador,
        ?float $toleranciaPct = null,
        ?string $observacion = null,
    ): Conciliacion {
        $tolerancia = $toleranciaPct ?? (float) config('milkflow.conciliacion.tolerancia_pct');

        return DB::transaction(function () use ($rutaAcopio, $litrosCaudalimetro, $registrador, $tolerancia, $observacion) {
            // Litros del acopiador = suma real de sus registros por productor.
            $litrosAcopiador = (float) $rutaAcopio->registros()->where('deleted', false)->sum('litros');

            if ($litrosAcopiador <= 0) {
                throw new ReglaNegocioException(
                    'La ruta de acopio no tiene litros registrados; no se puede conciliar.',
                    'CONCILIACION_SIN_REGISTROS',
                );
            }

            $diferencia = round($litrosCaudalimetro - $litrosAcopiador, 2);
            $porcentaje = round(abs($diferencia) / $litrosAcopiador * 100, 3);
            $tieneAlerta = $porcentaje > $tolerancia;

            $conciliacion = Conciliacion::updateOrCreate(
                ['ruta_acopio_id' => $rutaAcopio->id],
                [
                    'registrado_por' => $registrador->id,
                    'litros_acopiador' => $litrosAcopiador,
                    'litros_caudalimetro' => round($litrosCaudalimetro, 2),
                    'diferencia_litros' => $diferencia,
                    'diferencia_porcentaje' => $porcentaje,
                    'tolerancia_aplicada_pct' => $tolerancia,
                    'tiene_alerta' => $tieneAlerta,
                    // Lo que escribe el operador manda; si no escribió nada y
                    // hay alerta, queda al menos el motivo automático.
                    'observacion' => trim((string) $observacion) !== ''
                        ? trim((string) $observacion)
                        : ($tieneAlerta ? "Diferencia {$porcentaje}% supera la tolerancia {$tolerancia}%." : null),
                    'conciliado_en' => now(),
                ],
            );

            $rutaAcopio->forceFill(['estado' => 'conciliada'])->save();

            return $conciliacion;
        });
    }
}
