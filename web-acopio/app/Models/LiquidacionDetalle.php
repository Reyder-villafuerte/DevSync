<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiquidacionDetalle extends Model
{
    protected $table = 'liquidacion_detalles';

    protected $guarded = ['id'];

    public function entrega()
    {
        return $this->belongsTo(Entrega::class);
    }
}
