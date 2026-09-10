<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acopiador extends Model
{
    use Sincronizable;

    protected $table = 'acopiadores';

    protected $fillable = ['usuario_id', 'ruta_id', 'vigente_desde', 'vigente_hasta'];

    protected function casts(): array
    {
        return ['vigente_desde' => 'date', 'vigente_hasta' => 'date'];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    public function rutasAcopio(): HasMany
    {
        return $this->hasMany(RutaAcopio::class, 'acopiador_id');
    }

    public function scopeVigentes($q)
    {
        return $q->whereNull('vigente_hasta');
    }
}
