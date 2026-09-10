<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DescargaTina extends Model
{
    use Sincronizable;

    protected $table = 'descargas_tina';

    protected $fillable = [
        'ruta_acopio_id', 'tina', 'litros_descargados', 'hora_descarga', 'recibido_por',
    ];

    protected function casts(): array
    {
        return ['litros_descargados' => 'decimal:2', 'hora_descarga' => 'datetime'];
    }

    public function rutaAcopio(): BelongsTo
    {
        return $this->belongsTo(RutaAcopio::class, 'ruta_acopio_id');
    }
}
