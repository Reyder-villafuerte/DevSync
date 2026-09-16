<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capacitacion extends Model
{
    protected $table = 'capacitaciones';

    protected $guarded = ['id'];

    public function productor()
    {
        return $this->belongsTo(Productor::class);
    }
}
