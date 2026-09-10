<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Producto extends Model
{
    use HasFactory, Sincronizable;

    protected $table = 'productos';

    protected $fillable = [
        'nombre', 'tipo', 'unidad_medida',
        'rendimiento_min_por_100l', 'rendimiento_max_por_100l',
        'controla_rendimiento', 'activo',
    ];

    protected function casts(): array
    {
        return [
            'controla_rendimiento' => 'boolean',
            'activo' => 'boolean',
            'rendimiento_min_por_100l' => 'decimal:2',
            'rendimiento_max_por_100l' => 'decimal:2',
        ];
    }

    public function preciosVenta(): HasMany
    {
        return $this->hasMany(PrecioVenta::class, 'producto_id');
    }

    public function movimientosStock(): HasMany
    {
        return $this->hasMany(MovimientoStock::class, 'producto_id');
    }

    public function stockActual(): HasOne
    {
        return $this->hasOne(StockActual::class, 'producto_id');
    }
}
