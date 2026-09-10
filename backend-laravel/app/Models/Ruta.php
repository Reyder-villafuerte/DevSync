<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ruta extends Model
{
    use Sincronizable;

    protected $table = 'rutas';

    protected $fillable = ['nombre', 'codigo', 'descripcion', 'activa'];

    protected function casts(): array
    {
        return ['activa' => 'boolean'];
    }

    public function zonas(): HasMany
    {
        return $this->hasMany(Zona::class, 'ruta_id');
    }

    public function acopiadores(): HasMany
    {
        return $this->hasMany(Acopiador::class, 'ruta_id');
    }
}
