<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Proyección de solo lectura sobre la vista materializada `stock_actual`.
 * No se inserta ni actualiza desde Eloquent: la refresca StockService.
 */
class StockActual extends Model
{
    protected $table = 'stock_actual';

    protected $primaryKey = 'producto_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'cantidad_actual' => 'decimal:2',
            'ultimo_movimiento_en' => 'datetime',
            'total_movimientos' => 'integer',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }
}
