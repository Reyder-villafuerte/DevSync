<?php

namespace App\Models;

use App\Services\Produccion\CatalogoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Producto terminado del catálogo (queso, yogurt de fresa, mantequilla).
 *
 * El rendimiento ya no es una constante de PHP: lo que consume una unidad vive
 * en `recipeItems`, y lo que tarda el proceso en `process_hours`.
 */
class Product extends Model
{
    protected $fillable = [
        'inventory_category_id',
        'item_code',
        'name',
        'unit',
        'measurement_unit_id',
        'process_hours',
        'price_provider',
        'price_wholesale',
        'price_local',
        'process_notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'process_hours' => 'decimal:2',
        'price_provider' => 'decimal:2',
        'price_wholesale' => 'decimal:2',
        'price_local' => 'decimal:2',
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

    public function recipeItems(): HasMany
    {
        return $this->hasMany(ProductRecipeItem::class);
    }

    /** Tarifas propias de este producto, una por tipo de cliente. */
    public function clientTypePrices(): HasMany
    {
        return $this->hasMany(ProductClientTypePrice::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(ProductionOrder::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stock(): float
    {
        return InventoryStock::getStock($this->item_code);
    }

    /**
     * Tarifa que corresponde al comprador.
     *
     * Ya no es una escalera fija de tres peldaños: se resuelve qué tipo de
     * cliente le toca (por su rol del padrón, por el tipo que tenga asignado o
     * por la cantidad que se lleva) y se cobra la tarifa de ese tipo para este
     * producto, o su estándar si el producto no tiene una propia.
     */
    public function priceForCustomer(?Customer $customer, int $quantity = 1): float
    {
        return $this->tarifaPara($customer, $quantity) ?? 0.0;
    }

    /**
     * La tarifa que le toca a ese comprador, o null si nadie la cargó.
     *
     * El mostrador usa esta versión para poder negarse a vender en vez de
     * cobrar cero.
     */
    public function tarifaPara(?Customer $customer, int $quantity = 1): ?float
    {
        $tipo = ClientType::paraCliente($customer, $quantity);

        return $tipo?->priceFor($this);
    }

    /** Lo que este producto le cuesta a un tipo de cliente concreto. */
    public function priceForClientType(ClientType $tipo): ?float
    {
        return $tipo->priceFor($this);
    }

    /**
     * Empuja las tres columnas heredadas a la tabla de tarifas.
     *
     * `price_provider`, `price_wholesale` y `price_local` siguen existiendo
     * porque la app móvil las lee en su sincronización. Mientras las dos
     * representaciones convivan tienen que decir lo mismo, y la de la tabla es
     * la que cobra el mostrador.
     */
    public function syncTarifasBase(): void
    {
        $porSlug = [
            'proveedor' => $this->price_provider,
            'mayorista' => $this->price_wholesale,
            'local' => $this->price_local,
        ];

        foreach (ClientType::whereIn('slug', array_keys($porSlug))->get() as $tipo) {
            ProductClientTypePrice::updateOrCreate(
                ['product_id' => $this->id, 'client_type_id' => $tipo->id],
                ['price_per_unit' => $porSlug[$tipo->slug]],
            );
        }
    }

    /**
     * Lo que cuesta fabricar UNA unidad según su receta.
     *
     * Si un renglón es otro producto de la planta, se baja a su propia receta:
     * el costo del sándwich incluye lo que costó el queso que lleva dentro. La
     * profundidad está acotada por si una receta vieja quedó en ciclo; las
     * nuevas no pueden crearlo, {@see CatalogoService}.
     */
    public function recipeCost(int $profundidad = 0): float
    {
        if ($profundidad > 10) {
            return 0.0;
        }

        $receta = $this->relationLoaded('recipeItems')
            ? $this->recipeItems
            : $this->recipeItems()->with(['supply', 'componentProduct'])->get();

        return (float) $receta->sum(
            fn (ProductRecipeItem $item) => (float) $item->quantity_per_unit * $item->costoUnitario($profundidad)
        );
    }

    /** Margen sobre la tarifa de público local, que es la más alta. */
    public function localMargin(): float
    {
        return (float) $this->price_local - $this->recipeCost();
    }

    /**
     * Unidades que alcanzan a producirse con lo que hay hoy en almacén.
     *
     * Mira el stock real de cada ingrediente, sea insumo o producto componente:
     * no cuenta lo que ese componente «podría» fabricarse a su vez.
     */
    public function maximumProducible(): float
    {
        $receta = $this->recipeItems()->with(['supply', 'componentProduct'])->get();

        if ($receta->isEmpty()) {
            return 0.0;
        }

        return (float) $receta->map(function (ProductRecipeItem $item) {
            $porUnidad = (float) $item->quantity_per_unit;

            return $porUnidad > 0 ? floor($item->stockDisponible() / $porUnidad) : 0.0;
        })->min();
    }

    /** Recetas donde este producto entra como componente de otro. */
    public function usedInRecipes(): HasMany
    {
        return $this->hasMany(ProductRecipeItem::class, 'component_product_id');
    }
}
