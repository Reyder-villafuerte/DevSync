<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Da forma a la respuesta de /api/sync/pull. El SyncService ya entrega las
 * filas en camelCase; aquí solo se fija el contrato externo.
 *
 * @property array $resource
 */
class SyncPullResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        return [
            'servidorEn' => $this->resource['servidorEn'],
            // Timestamp a enviar como ?desde= en el próximo pull.
            'cursor' => $this->resource['cursor'],
            'hayMas' => $this->resource['hayMas'],
            'ambito' => $this->resource['ambito'],
            // { entidadCamel: [ {fila camelCase, incluye deleted}, ... ] }
            'cambios' => $this->resource['cambios'],
        ];
    }
}
