<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Zona extends Model
{
    use Sincronizable;

    protected $table = 'zonas';

    protected $fillable = ['nombre', 'codigo', 'ruta_id', 'latitud', 'longitud', 'activa'];

    protected function casts(): array
    {
        return ['activa' => 'boolean', 'latitud' => 'decimal:7', 'longitud' => 'decimal:7'];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    public function productores(): HasMany
    {
        return $this->hasMany(Productor::class, 'zona_id');
    }
}
