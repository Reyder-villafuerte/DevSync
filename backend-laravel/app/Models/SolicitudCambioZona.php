<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudCambioZona extends Model
{
    use Sincronizable;

    protected $table = 'solicitudes_cambio_zona';

    protected $fillable = [
        'productor_id', 'zona_actual_id', 'zona_solicitada_id', 'motivo', 'estado',
        'resuelto_por', 'resuelto_en', 'comentario_resolucion',
    ];

    protected function casts(): array
    {
        return ['resuelto_en' => 'datetime'];
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }

    public function zonaActual(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_actual_id');
    }

    public function zonaSolicitada(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_solicitada_id');
    }

    public function scopePendientes($q)
    {
        return $q->where('estado', 'pendiente');
    }
}
