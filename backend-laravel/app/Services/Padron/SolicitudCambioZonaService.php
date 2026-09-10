<?php

namespace App\Services\Padron;

use App\Exceptions\ReglaNegocioException;
use App\Models\SolicitudCambioZona;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Use Case: resolución de solicitudes de cambio de ruta/zona de un productor.
 *
 * En el modelo de datos el productor pertenece a una `zona` (y la zona a una
 * `ruta`), así que "cambio de ruta" se resuelve moviendo la zona del productor.
 * Al APROBAR se actualiza `productor.zona_id` y se cierra la solicitud, todo en
 * una transacción.
 */
class SolicitudCambioZonaService
{
    public function aprobar(SolicitudCambioZona $solicitud, Usuario $resolutor, ?string $comentario = null): void
    {
        $this->asegurarPendiente($solicitud);

        DB::transaction(function () use ($solicitud, $resolutor, $comentario) {
            $solicitud->productor->forceFill(['zona_id' => $solicitud->zona_solicitada_id])->save();

            $solicitud->forceFill([
                'estado' => 'aprobada',
                'resuelto_por' => $resolutor->id,
                'resuelto_en' => now(),
                'comentario_resolucion' => $comentario,
            ])->save();
        });
    }

    public function rechazar(SolicitudCambioZona $solicitud, Usuario $resolutor, ?string $comentario = null): void
    {
        $this->asegurarPendiente($solicitud);

        $solicitud->forceFill([
            'estado' => 'rechazada',
            'resuelto_por' => $resolutor->id,
            'resuelto_en' => now(),
            'comentario_resolucion' => $comentario,
        ])->save();
    }

    private function asegurarPendiente(SolicitudCambioZona $solicitud): void
    {
        if ($solicitud->estado !== 'pendiente') {
            throw new ReglaNegocioException(
                "La solicitud ya fue {$solicitud->estado}.",
                'SOLICITUD_NO_PENDIENTE',
            );
        }
    }
}
