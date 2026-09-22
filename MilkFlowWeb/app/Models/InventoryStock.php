<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Existencia física de un ítem del almacén, identificada por su `item_code`.
 *
 * La fila sabe qué representa: un insumo (`kind = insumo`, se compra y se
 * consume) o un producto terminado (`kind = producto`, se produce y se vende).
 * Esa distinción es la que permite separar costo de compra y precio de venta.
 */
class InventoryStock extends Model
{
    public const INSUMO = 'insumo';

    public const PRODUCTO = 'producto';

    protected $fillable = [
        'item_code',
        'item_name',
        'kind',
        'supply_id',
        'product_id',
        'inventory_category_id',
        'current_stock',
        'unit',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function esInsumo(): bool
    {
        return $this->kind === self::INSUMO;
    }

    public function esProducto(): bool
    {
        return $this->kind === self::PRODUCTO;
    }

    public static function getStock(string $code): float
    {
        $item = static::where('item_code', $code)->first();

        return $item ? (float) $item->current_stock : 0.0;
    }

    public static function adjustStock(string $code, float $delta, string $name = '', string $unit = ''): static
    {
        $item = static::firstOrCreate(
            ['item_code' => $code],
            ['item_name' => $name ?: $code, 'current_stock' => 0, 'unit' => $unit ?: 'unidades']
        );
        $item->current_stock = max(0, (float) $item->current_stock + $delta);
        $item->save();

        // Las pantallas viejas (leche cruda, moldes de queso) mueven el stock por
        // código suelto. Si la fila todavía no sabe qué es, se clasifica sola
        // contra el catálogo en lugar de quedar huérfana.
        if ($item->kind === null) {
            $item->clasificarPorCodigo();
        }

        return $item;
    }

    /** Ata la fila al insumo o producto que comparte su `item_code`. */
    public function clasificarPorCodigo(): static
    {
        if ($insumo = Supply::where('item_code', $this->item_code)->first()) {
            return $this->vincularInsumo($insumo);
        }

        if ($producto = Product::where('item_code', $this->item_code)->first()) {
            return $this->vincularProducto($producto);
        }

        return $this;
    }

    public function vincularInsumo(Supply $insumo): static
    {
        $this->update([
            'kind' => self::INSUMO,
            'supply_id' => $insumo->id,
            'product_id' => null,
            'inventory_category_id' => $insumo->inventory_category_id,
        ]);

        return $this;
    }

    public function vincularProducto(Product $producto): static
    {
        $this->update([
            'kind' => self::PRODUCTO,
            'product_id' => $producto->id,
            'supply_id' => null,
            'inventory_category_id' => $producto->inventory_category_id,
        ]);

        return $this;
    }
}
