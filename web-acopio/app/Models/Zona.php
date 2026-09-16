<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    protected $table = 'zonas';

    protected $guarded = ['id'];

    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }

    public function sectores()
    {
        return $this->hasMany(Sector::class);
    }
}
