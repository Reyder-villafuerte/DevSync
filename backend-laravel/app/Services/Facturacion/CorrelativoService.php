<?php

namespace App\Services\Facturacion;

use App\Exceptions\ReglaNegocioException;
use App\Models\Correlativo;
use App\Models\Dispositivo;
use App\Models\RangoCorrelativo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: correlativo de facturación con RESERVA DE RANGOS POR DISPOSITIVO
 * (requisito técnico 6).
 *
 * Garantías:
 *  - Sin repeticiones: la restricción de exclusión GiST sobre int8range impide
 *    dos rangos solapados en la misma serie, aunque dos reservas corran a la
 *    vez.
 *  - Sin huecos: cada reserva arranca en numero_maximo_asignado + 1, contiguo
 *    al rango anterior. El bloqueo de fila (lockForUpdate) sobre el correlativo
 *    serializa las reservas.
 *  - Offline: el dispositivo consume su rango sin red; al sincronizar solo
 *    reporta los números ya usados.
 */
class CorrelativoService
{
    /** Reserva el siguiente bloque de números para un dispositivo. */
    public function reservarRango(Correlativo $correlativo, Dispositivo $dispositivo, ?int $tamano = null): RangoCorrelativo
    {
        $tamano ??= $correlativo->tamano_bloque_default ?: (int) config('milkflow.facturacion.tamano_bloque_default');
        if ($tamano < 1) {
            throw new ReglaNegocioException('El tamaño de bloque debe ser al menos 1.', 'CORRELATIVO_BLOQUE_INVALIDO');
        }

        return DB::transaction(function () use ($correlativo, $dispositivo, $tamano) {
            // Bloqueo de la fila de definición: nadie más avanza el contador
            // mientras calculamos el rango.
            $bloqueado = Correlativo::query()->whereKey($correlativo->id)->lockForUpdate()->firstOrFail();

            $desde = $bloqueado->numero_maximo_asignado + 1;
            $hasta = $desde + $tamano - 1;

            $rango = RangoCorrelativo::create([
                'correlativo_id' => $bloqueado->id,
                'dispositivo_id' => $dispositivo->id,
                'numero_desde' => $desde,
                'numero_hasta' => $hasta,
                'numero_siguiente' => $desde,
                'agotado' => false,
                'reservado_en' => now(),
            ]);

            $bloqueado->forceFill(['numero_maximo_asignado' => $hasta])->save();

            return $rango;
        });
    }

    /**
     * Consume el siguiente número dentro de un rango (al emitir un
     * comprobante). Devuelve el número emitido.
     */
    public function emitirNumero(RangoCorrelativo $rango): int
    {
        return DB::transaction(function () use ($rango) {
            $bloqueado = RangoCorrelativo::query()->whereKey($rango->id)->lockForUpdate()->firstOrFail();

            if ($bloqueado->agotado || $bloqueado->numero_siguiente > $bloqueado->numero_hasta) {
                throw new ReglaNegocioException(
                    'El rango de correlativos del dispositivo está agotado; reserve uno nuevo.',
                    'CORRELATIVO_RANGO_AGOTADO',
                    ['rango_id' => $bloqueado->id],
                );
            }

            $numero = $bloqueado->numero_siguiente;
            $bloqueado->numero_siguiente = $numero + 1;
            $bloqueado->agotado = $bloqueado->numero_siguiente > $bloqueado->numero_hasta;
            $bloqueado->save();

            return $numero;
        });
    }

    /**
     * Cierre del día: libera los rangos vigentes del dispositivo.
     *
     * - Se marcan como `agotado` para forzar una reserva nueva al día siguiente.
     * - Si la cola sin usar de un rango llega hasta el tope del correlativo
     *   (es el último reservado), se devuelve al pool retrocediendo
     *   `numero_maximo_asignado`: así no quedan huecos por los números que el
     *   dispositivo no alcanzó a emitir. Si en el medio se reservó otro rango,
     *   la cola no se puede reclamar y esos números quedan anulados.
     *
     * @return Collection<int, RangoCorrelativo> rangos liberados
     */
    public function liberarRangosDelDia(Dispositivo $dispositivo, ?Carbon $dia = null): Collection
    {
        $dia ??= Carbon::today();

        return DB::transaction(function () use ($dispositivo, $dia) {
            $rangos = RangoCorrelativo::query()
                ->where('dispositivo_id', $dispositivo->id)
                ->where('agotado', false)
                ->whereDate('reservado_en', $dia->toDateString())
                ->lockForUpdate()
                ->get();

            foreach ($rangos as $rango) {
                $colaSinUsar = $rango->numero_hasta - $rango->numero_siguiente + 1;

                if ($colaSinUsar > 0) {
                    $correlativo = Correlativo::query()->whereKey($rango->correlativo_id)->lockForUpdate()->first();
                    if ($correlativo && $correlativo->numero_maximo_asignado === $rango->numero_hasta) {
                        // Este rango es el último: se reclama la cola sin usar.
                        $correlativo->forceFill([
                            'numero_maximo_asignado' => $rango->numero_siguiente - 1,
                        ])->save();
                        $rango->numero_hasta = $rango->numero_siguiente - 1;
                    }
                }

                $rango->agotado = true;
                $rango->save();
            }

            return $rangos;
        });
    }
}
