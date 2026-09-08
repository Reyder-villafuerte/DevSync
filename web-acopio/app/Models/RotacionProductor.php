<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RotacionProductor extends Model
{
    protected $table = 'rotaciones_productores';

    protected $guarded = ['id'];

    public function productor()
    {
        return $this->belongsTo(Productor::class);
    }
}
