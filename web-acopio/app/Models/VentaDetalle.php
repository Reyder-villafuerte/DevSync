<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
    protected $table = 'venta_detalles';

    protected $guarded = ['id'];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }
}
