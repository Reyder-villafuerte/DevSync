<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Distrito extends Model
{
    protected $table = 'distritos';

    protected $guarded = ['id'];

    public function zonas()
    {
        return $this->hasMany(Zona::class);
    }
}
