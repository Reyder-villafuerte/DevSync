<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acopiador extends Model
{
    protected $table = 'acopiadores';

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class);
    }

    public function rutas()
    {
        return $this->hasMany(Ruta::class);
    }
}
