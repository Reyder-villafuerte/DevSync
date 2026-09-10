<?php

namespace App\Services\Acopio;

use App\Enums\EstadoRecepcion;
use App\Exceptions\ReglaNegocioException;
use App\Models\RegistroAcopio;
use App\Models\RutaAcopio;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: verificación en planta de cada entrega de una ruta de acopio.
 *
 * El acopiador declara los litros que le entregó cada productor en el campo.
 * Al cerrar la ruta y descargar en tina, el jefe de producción confirma —
 * entrega por entrega — cuántos litros recibió realmente (medidor de tina /
 * caudalímetro). El resultado (estado + litros faltantes) se CALCULA aquí y se
 * PERSISTE en el propio `registros_acopio`; nadie lo recalcula al leer.
 *
 * Esto es complementario a ConciliacionService, que compara el TOTAL de la
 * ruta contra el caudalímetro. Aquí el detalle es por productor.
 */
class VerificacionRecepcionService
{
    /**
     * Confirma una entrega. `$litrosRecibidos = null` significa "conforme":
     * el jefe recibió exactamente lo declarado.
     */
    public function confirmar(
        RegistroAcopio $registro,
        ?float $litrosRecibidos,
        Usuario $jefe,
        ?string $observacion = null,
    ): RegistroAcopio {
        if ($registro->deleted) {
            throw new ReglaNegocioException('La entrega fue anulada; no se puede verificar.', 'RECEPCION_ENTREGA_ANULADA');
        }

        $declarados = (float) $registro->litros;
        $recibidos = $litrosRecibidos ?? $declarados;

        if ($recibidos < 0) {
            throw new ReglaNegocioException('Los litros recibidos no pueden ser negativos.', 'RECEPCION_LITROS_NEGATIVOS');
        }

        $tolerancia = (float) config('milkflow.recepcion.tolerancia_litros');
        $diferencia = round($recibidos - $declarados, 2);

        $estado = match (true) {
            $diferencia < -$tolerancia => EstadoRecepcion::FALTANTE,
            $diferencia > $tolerancia => EstadoRecepcion::EXCEDENTE,
            default => EstadoRecepcion::CONFORME,
        };
        $faltantes = $estado === EstadoRecepcion::FALTANTE ? abs($diferencia) : 0.0;

        return DB::transaction(function () use ($registro, $recibidos, $faltantes, $estado, $observacion, $jefe) {
            $registro->forceFill([
                'estado_recepcion' => $estado,
                'litros_recibidos' => round($recibidos, 2),
                'litros_faltantes' => round($faltantes, 2),
                'recepcion_observacion' => $observacion,
                'recepcion_confirmada_en' => now(),
                'recepcion_confirmada_por' => $jefe->id,
            ])->save();

            return $registro->refresh();
        });
    }

    /**
     * Marca "conforme" todas las entregas de la ruta que aún estén pendientes.
     * Atajo para el caso normal en que todo llegó bien.
     *
     * @return int cantidad de entregas verificadas
     */
    public function confirmarLoteConforme(RutaAcopio $ruta, Usuario $jefe): int
    {
        return DB::transaction(function () use ($ruta, $jefe) {
            $pendientes = $ruta->registros()->where('deleted', false)->pendientesDeRecepcion()->get();

            foreach ($pendientes as $registro) {
                $this->confirmar($registro, null, $jefe);
            }

            return $pendientes->count();
        });
    }
}
