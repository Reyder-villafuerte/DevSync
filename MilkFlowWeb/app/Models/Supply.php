<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Insumo o materia prima. Su existencia física se lleva en `inventory_stocks`
 * a través del `item_code`, que es el mismo que ya usaba la leche cruda.
 */
class Supply extends Model
{
    /** Se compra a un proveedor y entra por la pantalla de Compras. */
    public const ENTRADA_COMPRA = 'compra';

    /**
     * Entra por otra vía del sistema y NO se compra: la leche llega por el
     * caudalímetro de planta y se paga por liquidación semanal al productor.
     * Registrarla además como compra la contaría y la pagaría dos veces.
     */
    public const ENTRADA_ACOPIO = 'acopio';

    public const ENTRADAS = [self::ENTRADA_COMPRA, self::ENTRADA_ACOPIO];

    protected $fillable = [
        'inventory_category_id',
        'item_code',
        'name',
        'unit',
        'measurement_unit_id',
        'unit_cost',
        'entry_mode',
        'minimum_stock',
        'is_active',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'minimum_stock' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(InventoryCategory::class, 'inventory_category_id');
    }

    public function measurementUnit(): BelongsTo
    {
        return $this->belongsTo(MeasurementUnit::class, 'measurement_unit_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SupplyMovement::class);
    }

    public function recipeItems(): HasMany
    {
        return $this->hasMany(ProductRecipeItem::class);
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stock(): float
    {
        return InventoryStock::getStock($this->item_code);
    }

    public function isBelowMinimum(): bool
    {
        return $this->stock() < (float) $this->minimum_stock;
    }

    public function seCompra(): bool
    {
        return $this->entry_mode !== self::ENTRADA_ACOPIO;
    }

    public function etiquetaEntrada(): string
    {
        return $this->seCompra() ? 'Se compra' : 'Entra por acopio';
    }
}
