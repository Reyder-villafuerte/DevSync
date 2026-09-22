<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Un renglón de la receta: cuánto consume UNA unidad del producto.
 *
 * El renglón apunta a una de dos cosas, nunca a las dos: un insumo comprado
 * (`supply_id`) o un producto que la planta ya fabricó (`component_product_id`),
 * que es como se expresa un semielaborado o un sándwich que lleva un queso.
 */
class ProductRecipeItem extends Model
{
    protected $fillable = [
        'product_id',
        'supply_id',
        'component_product_id',
        'quantity_per_unit',
        'notes',
    ];

    protected $casts = [
        'quantity_per_unit' => 'decimal:4',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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

    /** El insumo o el producto al que apunta este renglón. */
    public function ingrediente(): Supply|Product|null
    {
        return $this->esProducto() ? $this->componentProduct : $this->supply;
    }

    public function nombreIngrediente(): string
    {
        return $this->ingrediente()?->name ?? 'Ingrediente eliminado';
    }

    public function unidadIngrediente(): string
    {
        return $this->ingrediente()?->unit ?? '';
    }

    /** Lo que hay en almacén de este ingrediente, sea insumo o producto. */
    public function stockDisponible(): float
    {
        $ingrediente = $this->ingrediente();

        return $ingrediente ? InventoryStock::getStock($ingrediente->item_code) : 0.0;
    }

    /**
     * Cuánto cuesta una unidad del ingrediente: el costo de compra si es insumo,
     * o el costo de su propia receta si es un producto de la planta.
     */
    public function costoUnitario(int $profundidad = 0): float
    {
        $ingrediente = $this->ingrediente();

        if (! $ingrediente) {
            return 0.0;
        }

        return $ingrediente instanceof Product
            ? $ingrediente->recipeCost($profundidad + 1)
            : (float) $ingrediente->unit_cost;
    }
}
