<?php

namespace App\Http\Resources;

use App\Services\Sync\ResultadoPush;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property ResultadoPush $resource
 */
class PushResultadoResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'servidorEn' => now()->toISOString(),
            // Operaciones aplicadas: { id, entidad, version, resultado:
            //   insertado | actualizado | eliminado | idempotente }
            'aceptadas' => $this->resource->aceptadas,
            // Conflictos: { id, entidad, motivo, servidor: {estado actual en camelCase} }
            'conflictos' => $this->resource->conflictos,
            // Rechazos por validación/integridad/rol: { id, entidad, motivo }
            'rechazadas' => $this->resource->rechazadas,
            'resumen' => $this->resource->resumen(),
        ];
    }
}
