<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sancion extends Model
{
    protected $table = 'sanciones';

    protected $guarded = ['id'];

    public function productor()
    {
        return $this->belongsTo(Productor::class);
    }
}
