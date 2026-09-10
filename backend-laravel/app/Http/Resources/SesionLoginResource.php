<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array $resource  ['token','usuario','ambito','dispositivoId']
 */
class SesionLoginResource extends JsonResource
{
    public static $wrap = null;

    public function toArray($request): array
    {
        $u = $this->resource['usuario'];

        return [
            'token' => $this->resource['token'],
            'dispositivoId' => $this->resource['dispositivoId'],
            'usuario' => [
                'id' => $u->id,
                'dni' => $u->dni,
                'nombres' => $u->nombres,
                'apellidos' => $u->apellidos,
                'rol' => $u->rol->value,
                'rolEtiqueta' => $u->rol->etiqueta(),
            ],
            // Ámbito asignado: el móvil lo usa como valor por defecto de ?ambito.
            'ambito' => $this->resource['ambito'],
        ];
    }
}
