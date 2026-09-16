<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProblemaLeche extends Model
{
    protected $table = 'problemas_leche';

    protected $guarded = ['id'];

    public function entrega()
    {
        return $this->belongsTo(Entrega::class);
    }
}
