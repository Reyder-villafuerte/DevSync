<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asamblea extends Model
{
    use Sincronizable;

    protected $table = 'asambleas';

    protected $fillable = [
        'titulo', 'descripcion', 'fecha', 'lugar', 'tipo',
        'padron_snapshot', 'quorum_requerido', 'fraccion_quorum',
        'asistentes', 'quorum_alcanzado', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'padron_snapshot' => 'integer',
            'quorum_requerido' => 'integer',
            'fraccion_quorum' => 'decimal:3',
            'asistentes' => 'integer',
            'quorum_alcanzado' => 'boolean',
        ];
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(AsistenciaAsamblea::class, 'asamblea_id');
    }
}
