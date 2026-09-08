<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CierreRuta extends Model
{
    protected $table = 'cierres_ruta';

    protected $guarded = ['id'];

    public function ruta()
    {
        return $this->belongsTo(Ruta::class);
    }
}
