<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoteProduccion extends Model
{
    protected $table = 'lotes_produccion';

    protected $guarded = ['id'];

    public function entregas()
    {
        return $this->belongsToMany(Entrega::class, 'lote_entregas', 'lote_id')->withPivot('litros');
    }
}
