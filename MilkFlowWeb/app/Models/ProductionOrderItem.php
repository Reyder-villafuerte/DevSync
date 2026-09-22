<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Consumo real dentro de un lote; queda como historia del lote.
 *
 * Como la receta, apunta a un insumo o a un producto componente, nunca a los
 * dos.
 */
class ProductionOrderItem extends Model
{
    protected $fillable = [
        'production_order_id',
        'supply_id',
        'component_product_id',
        'quantity_used',
        'unit',
    ];

    protected $casts = [
        'quantity_used' => 'decimal:4',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function componentProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'component_product_id');
    }

    public function esProducto(): bool
    {
        return $this->component_product_id !== null;
    }

    public function ingrediente(): Supply|Product|null
    {
        return $this->esProducto() ? $this->componentProduct : $this->supply;
    }

    public function nombreIngrediente(): string
    {
        return $this->ingrediente()?->name ?? 'Ingrediente eliminado';
    }
}
