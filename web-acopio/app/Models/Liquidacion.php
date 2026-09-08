<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Liquidacion extends Model
{
    protected $table = 'liquidaciones';

    protected $guarded = ['id'];

    public function productor()
    {
        return $this->belongsTo(Productor::class);
    }

    public function detalles()
    {
        return $this->hasMany(LiquidacionDetalle::class);
    }
}
