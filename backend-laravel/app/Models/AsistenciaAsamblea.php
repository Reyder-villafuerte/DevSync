<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaAsamblea extends Model
{
    use Sincronizable;

    protected $table = 'asistencias_asamblea';

    protected $fillable = [
        'asamblea_id', 'productor_id', 'dni', 'nombre_completo',
        'registrado_en', 'registrado_por',
    ];

    protected function casts(): array
    {
        return ['registrado_en' => 'datetime'];
    }

    public function asamblea(): BelongsTo
    {
        return $this->belongsTo(Asamblea::class, 'asamblea_id');
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }
}
