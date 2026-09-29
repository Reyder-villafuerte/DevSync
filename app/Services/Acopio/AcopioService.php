<?php

namespace App\Services\Acopio;

use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionRecord;
use App\Models\CollectionRoute;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;

/**
 * Reglas del acopio en ruta (4:30 AM).
 *
 * Única implementación para el panel web y para la app móvil: el controlador
 * web y el comando de sincronización llaman a los mismos métodos.
 */
class AcopioService
{
    /**
     * Devuelve la ruta del día del acopiador, creándola si aún no existe.
     *
     * Auto-asignación: se prefiere la zona del propio acopiador; si ya está
     * tomada, se le asigna cualquier zona activa libre. Si las 4 zonas de Huata
     * están cubiertas devuelve null (turno de descanso por rotación).
     */
    public function rutaDelDia(User $acopiador, ?string $fecha = null, ?string $clientUuid = null): ?CollectionRoute
    {
        $fecha = $fecha ?: app(JornadaOperativa::class)->fecha();

        $ruta = CollectionRoute::where('date', $fecha)
            ->where('collector_id', $acopiador->id)
            ->first();

        if ($ruta) {
            // El móvil pudo abrir la ruta sin señal con su propio uuid: se adopta
            // para que el dispositivo pueda enlazar su fila local con la del servidor.
            if ($clientUuid && ! $ruta->client_uuid) {
                $ruta->client_uuid = $clientUuid;
                $ruta->save();
            }

            return $ruta;
        }

        if ($clientUuid) {
            $ruta = CollectionRoute::where('client_uuid', $clientUuid)->first();
            if ($ruta && $ruta->collector_id === $acopiador->id && $ruta->date === $fecha) {
                return $ruta;
            }
        }

        $zonasOcupadas = CollectionRoute::where('date', $fecha)->pluck('zone_id')->all();

        $zonaDestino = ($acopiador->zone_id && ! in_array($acopiador->zone_id, $zonasOcupadas))
            ? $acopiador->zone_id
            : Zone::where('is_active', true)->whereNotIn('id', $zonasOcupadas)->value('id');

        if (! $zonaDestino) {
            return null;
        }

        return CollectionRoute::create([
            'client_uuid' => $clientUuid,
            'date' => $fecha,
            'zone_id' => $zonaDestino,
            'collector_id' => $acopiador->id,
            'start_time' => '04:30:00',
            'status' => 'asignada',
            'total_collected_liters' => 0,
        ]);
    }

    /**
     * Registra o corrige la entrega de un productor dentro de una ruta.
     *
     * Es idempotente por (ruta, productor): reenviar la misma entrega actualiza
     * la fila existente en lugar de duplicarla.
     */
    public function registrarEntrega(
        CollectionRoute $ruta,
        int $productorId,
        float $litros,
        ?string $notas = null,
        ?string $hora = null,
        ?string $clientUuid = null
    ): CollectionRecord {
        if (in_array($ruta->status, ['descargada_planta', 'verificada'], true)) {
            throw new ReglaNegocioException(
                'La ruta ya fue verificada en planta con caudalímetro y no admite cambios.',
                'liters'
            );
        }

        return DB::transaction(function () use ($ruta, $productorId, $litros, $notas, $clientUuid) {
            $ruta = CollectionRoute::whereKey($ruta->id)->lockForUpdate()->firstOrFail();

            if ($ruta->date !== app(JornadaOperativa::class)->fecha()) {
                throw new ReglaNegocioException('Esta ruta no corresponde a la jornada actual.', 'producer_id');
            }

            if (! User::whereKey($productorId)->where('role', 'productor')->where('zone_id', $ruta->zone_id)->exists()) {
                throw new ReglaNegocioException('El proveedor no pertenece a esta ruta.', 'producer_id');
            }

            if ($ruta->records()->where('producer_id', $productorId)->exists()) {
                throw new ReglaNegocioException('Este proveedor ya fue registrado en la jornada actual.', 'producer_id');
            }

            $registro = CollectionRecord::create(
                [
                    'collection_route_id' => $ruta->id,
                    'producer_id' => $productorId,
                    'client_uuid' => $clientUuid,
                    'liters' => $litros,
                    'collected_at' => app(JornadaOperativa::class)->hora(),
                    'notes' => $notas,
                ]
            );

            $ruta->total_collected_liters = $ruta->records()->sum('liters');
            if ($ruta->status === 'asignada') {
                $ruta->status = 'en_ruta';
            }
            $ruta->save();

            return $registro;
        });
    }

    /**
     * El acopiador cierra la ruta y la descarga en planta.
     * Queda esperando la verificación con caudalímetro del jefe de producción.
     *
     * Cerrar dos veces no hace daño y el teléfono lo reintenta cuando se le
     * corta la señal, así que se deja pasar sin ruido. Lo que no se deja es
     * cerrar una ruta YA verificada: el caudalímetro es lo último que toca
     * esa ruta, y devolverla a «descargada» la haría aparecer otra vez como
     * pendiente de medir.
     */
    public function cerrarRuta(CollectionRoute $ruta): CollectionRoute
    {
        if ($ruta->status === 'verificada') {
            throw new ReglaNegocioException(
                'Esta ruta ya fue verificada en planta con caudalímetro: no se puede volver a descargar.',
                'status'
            );
        }

        if ($ruta->status === 'descargada_planta') {
            return $ruta;
        }

        $ruta->total_collected_liters = $ruta->records()->sum('liters');
        $ruta->status = 'descargada_planta';
        $ruta->save();

        return $ruta;
    }

    /** Administración asigna manualmente una zona a un acopiador para una fecha. */
    public function asignarRuta(string $fecha, int $zonaId, int $acopiadorId, ?string $horaInicio = null): CollectionRoute
    {
        return DB::transaction(fn (): CollectionRoute => CollectionRoute::updateOrCreate(
            ['date' => $fecha, 'zone_id' => $zonaId],
            [
                'collector_id' => $acopiadorId,
                'start_time' => $horaInicio ?: '04:30:00',
                'status' => 'asignada',
            ]
        ));
    }
}
