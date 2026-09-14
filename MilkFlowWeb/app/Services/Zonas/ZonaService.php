<?php

namespace App\Services\Zonas;

use App\Exceptions\ReglaNegocioException;
use App\Models\User;
use App\Models\ZoneChangeRequest;
use Illuminate\Support\Facades\DB;

/**
 * Solicitudes de cambio de zona del productor y su revisión por administración.
 */
class ZonaService
{
    public function solicitarCambio(
        User $productor,
        int $zonaSolicitadaId,
        ?string $motivo = null,
        ?string $clientUuid = null
    ): ZoneChangeRequest {
        $zonaActual = $productor->zone_id ?: $zonaSolicitadaId;

        if ($productor->zone_id && $productor->zone_id === $zonaSolicitadaId) {
            throw new ReglaNegocioException(
                'La zona solicitada es la misma que la actual.',
                'requested_zone_id'
            );
        }

        return ZoneChangeRequest::create([
            'client_uuid' => $clientUuid,
            'producer_id' => $productor->id,
            'current_zone_id' => $zonaActual,
            'requested_zone_id' => $zonaSolicitadaId,
            'reason' => $motivo,
            'status' => 'pendiente',
        ]);
    }

    /** Aprobar mueve al productor de zona; rechazar solo deja constancia. */
    public function revisar(ZoneChangeRequest $solicitud, User $revisor, string $decision): ZoneChangeRequest
    {
        if (!in_array($decision, ['aprobado', 'rechazado'], true)) {
            throw new ReglaNegocioException("Decisión no válida: {$decision}.", 'decision');
        }

        return DB::transaction(function () use ($solicitud, $revisor, $decision) {
            $solicitud->status = $decision;
            $solicitud->reviewed_by = $revisor->id;
            $solicitud->reviewed_at = now();
            $solicitud->save();

            if ($decision === 'aprobado') {
                $productor = $solicitud->producer;
                $productor->zone_id = $solicitud->requested_zone_id;
                $productor->save();
            }

            return $solicitud;
        });
    }
}
