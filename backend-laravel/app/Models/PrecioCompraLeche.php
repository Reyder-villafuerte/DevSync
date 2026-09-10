<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PrecioCompraLeche extends Model
{
    use Sincronizable;

    protected $table = 'precios_compra_leche';

    protected $fillable = [
        'precio_litro', 'precio_litro_minimo', 'vigente_desde', 'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'precio_litro' => 'decimal:4',
            'precio_litro_minimo' => 'decimal:4',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function scopeVigenteEn(Builder $q, string $fecha): Builder
    {
        return $q->where('vigente_desde', '<=', $fecha)
            ->where(fn (Builder $b) => $b->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha));
    }
}
