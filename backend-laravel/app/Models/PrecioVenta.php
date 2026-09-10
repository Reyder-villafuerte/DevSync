<?php

namespace App\Models;

use App\Enums\TipoCliente;
use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrecioVenta extends Model
{
    use Sincronizable;

    protected $table = 'precios_venta';

    protected $fillable = [
        'producto_id', 'tipo_cliente', 'precio', 'vigente_desde', 'vigente_hasta',
    ];

    protected function casts(): array
    {
        return [
            'tipo_cliente' => TipoCliente::class,
            'precio' => 'decimal:2',
            'vigente_desde' => 'date',
            'vigente_hasta' => 'date',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    /** Precio cuya vigencia contiene la fecha dada (requisito técnico 4). */
    public function scopeVigenteEn(Builder $q, string $fecha): Builder
    {
        return $q->where('vigente_desde', '<=', $fecha)
            ->where(fn (Builder $b) => $b->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha));
    }
}
