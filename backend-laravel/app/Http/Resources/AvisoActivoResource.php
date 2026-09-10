<?php

namespace App\Http\Resources;

use App\Models\Aviso;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Aviso $resource
 */
class AvisoActivoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'contenido' => $this->contenido,
            'imagenUrl' => $this->imagen_url,
            'obligatorio' => $this->obligatorio,
            'fechaPublicacion' => $this->fecha_publicacion?->toISOString(),
            'fechaExpiracion' => $this->fecha_expiracion?->toISOString(),
            // Presente solo si se cargó la relación filtrada por el productor.
            'visto' => $this->when(
                $this->relationLoaded('vistos'),
                fn () => $this->vistos->isNotEmpty(),
            ),
        ];
    }
}
