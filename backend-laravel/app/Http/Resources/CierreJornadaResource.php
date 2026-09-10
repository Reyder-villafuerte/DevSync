<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array $resource  ['resultado' => ResultadoPush, 'jornada' => RutaAcopio]
 */
class CierreJornadaResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        $j = $this->resource['jornada'];

        return [
            'jornada' => [
                'id' => $j->id,
                'estado' => $j->estado,
                'fecha' => $j->fecha?->toDateString(),
                'horaCierre' => $j->hora_cierre?->toISOString(),
                'litrosDeclarados' => $j->litros_declarados,
                'registros' => $j->registros->count(),
                'version' => $j->version,
            ],
            'lote' => (new PushResultadoResource($this->resource['resultado']))->toArray($request),
        ];
    }
}
