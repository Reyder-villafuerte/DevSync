<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Capacitacion extends Model
{
    use Sincronizable;

    protected $table = 'capacitaciones';

    protected $fillable = [
        'productor_id', 'control_calidad_id', 'motivo', 'estado',
        'fecha_programada', 'fecha_completada',
    ];

    protected function casts(): array
    {
        return ['fecha_programada' => 'date', 'fecha_completada' => 'date'];
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }

    public function scopePendientes($q)
    {
        return $q->where('estado', 'pendiente');
    }
}
