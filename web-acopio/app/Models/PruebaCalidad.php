<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PruebaCalidad extends Model
{
    protected $table = 'pruebas_calidad';

    protected $guarded = ['id'];

    public function entrega()
    {
        return $this->belongsTo(Entrega::class);
    }
}
