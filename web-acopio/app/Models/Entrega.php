<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrega extends Model
{
    protected $table = 'entregas';

    protected $guarded = ['id'];

    public function productor()
    {
        return $this->belongsTo(Productor::class);
    }

    public function acopiador()
    {
        return $this->belongsTo(Acopiador::class);
    }

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }

    public function pruebaCalidad()
    {
        return $this->hasOne(PruebaCalidad::class);
    }

    public function problemas()
    {
        return $this->hasMany(ProblemaLeche::class);
    }
}
