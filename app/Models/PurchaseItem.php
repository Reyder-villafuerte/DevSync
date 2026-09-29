<?php

namespace App\Models;

use App\Services\Produccion\ComprasService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Renglón de una compra: cuánto de qué insumo entró y a qué precio.
 *
 * El conjunto de estos renglones es lo que define el costo promedio ponderado
 * de cada insumo, en {@see ComprasService}.
 */
class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'supply_id',
        'quantity',
        'unit_cost',
        'subtotal',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'subtotal' => 'decimal:2',
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }
}
