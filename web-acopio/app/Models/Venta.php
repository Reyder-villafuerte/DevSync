<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $table = 'ventas';

    protected $guarded = ['id'];

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }
}
