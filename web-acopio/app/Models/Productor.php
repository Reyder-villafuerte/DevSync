<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Productor extends Model
{
    protected $table = 'productores';

    protected $guarded = ['id'];

    use SoftDeletes;

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entregas()
    {
        return $this->hasMany(Entrega::class);
    }

    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function distrito()
    {
        return $this->belongsTo(Distrito::class);
    }

    public function liquidaciones()
    {
        return $this->hasMany(Liquidacion::class);
    }

    public function rotaciones()
    {
        return $this->hasMany(RotacionProductor::class);
    }
}
