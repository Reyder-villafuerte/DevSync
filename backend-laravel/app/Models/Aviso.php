<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aviso extends Model
{
    use Sincronizable;

    protected $table = 'avisos';

    protected $fillable = [
        'titulo', 'contenido', 'imagen_url', 'fecha_publicacion', 'fecha_expiracion',
        'obligatorio', 'publicado', 'creado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_publicacion' => 'datetime',
            'fecha_expiracion' => 'datetime',
            'obligatorio' => 'boolean',
            'publicado' => 'boolean',
        ];
    }

    public function vistos(): HasMany
    {
        return $this->hasMany(AvisoVisto::class, 'aviso_id');
    }

    public function scopeVigentes(Builder $q): Builder
    {
        $ahora = now();

        return $q->where('publicado', true)
            ->where('fecha_publicacion', '<=', $ahora)
            ->where(fn (Builder $b) => $b->whereNull('fecha_expiracion')->orWhere('fecha_expiracion', '>=', $ahora));
    }
}
