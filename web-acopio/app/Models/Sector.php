<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sector extends Model
{
    protected $table = 'sectores';

    protected $guarded = ['id'];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }
}
