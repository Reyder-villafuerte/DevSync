<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    protected $table = 'rutas';

    protected $guarded = ['id'];

    public function acopiador()
    {
        return $this->belongsTo(Acopiador::class);
    }

    public function sectores()
    {
        return $this->belongsToMany(Sector::class, 'ruta_sectores');
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class);
    }
}
