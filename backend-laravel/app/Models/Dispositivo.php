<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dispositivo extends Model
{
    use Sincronizable;

    protected $table = 'dispositivos';

    protected $fillable = [
        'usuario_id', 'identificador', 'nombre', 'plataforma',
        'ultima_sincronizacion_en', 'activo',
    ];

    protected function casts(): array
    {
        return ['activo' => 'boolean', 'ultima_sincronizacion_en' => 'datetime'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function rangosCorrelativo(): HasMany
    {
        return $this->hasMany(RangoCorrelativo::class, 'dispositivo_id');
    }
}
