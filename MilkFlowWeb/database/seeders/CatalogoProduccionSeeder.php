<?php

namespace Database\Seeders;

use App\Models\CollectionPriceRule;
use App\Models\InventoryCategory;
use App\Models\InventoryStock;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\Supply;
use App\Models\SystemPrice;
use App\Models\User;
use App\Services\Produccion\CatalogoService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Catálogo inicial de la planta.
 *
 * Deja la regla de hoy expresada como DATO y no como constante de PHP: el queso
 * es el primer producto del catálogo y sus 10 L por molde son un renglón de su
 * receta. Los insumos de un yogurt embotellado quedan sembrados como ejemplo de
 * que la planta puede crecer sin tocar el código.
 *
 * Es idempotente: se puede volver a correr sobre una base que ya lo tiene.
 */
class CatalogoProduccionSeeder extends Seeder
{
    private CatalogoService $catalogo;

    public function run(): void
    {
        $this->catalogo = app(CatalogoService::class);
        $jefePlanta = User::where('role', 'jefe_produccion')->first();

        // Unidades de medida configurables: la planta puede agregar las suyas.
        $unidades = collect([
            ['name' => 'Litros', 'abbreviation' => 'L'],
            ['name' => 'Mililitros', 'abbreviation' => 'ml'],
            ['name' => 'Gramos', 'abbreviation' => 'g'],
            ['name' => 'Kilogramos', 'abbreviation' => 'kg'],
            ['name' => 'Unidades', 'abbreviation' => 'und'],
            ['name' => 'Moldes', 'abbreviation' => 'mold'],
        ])->mapWithKeys(function (array $unidad) {
            $medida = MeasurementUnit::firstOrCreate(
                ['abbreviation' => $unidad['abbreviation']],
                ['name' => $unidad['name'], 'is_active' => true]
            );

            return [$unidad['abbreviation'] => $medida];
        });

        $lacteo = $this->categoria('Lácteo base', 'Leche cruda verificada en caudalímetro.', $jefePlanta);
        $cultivos = $this->categoria('Cultivos e insumos', 'Cuajo, sal y cultivos lácticos.', $jefePlanta);
        $frutas = $this->categoria('Frutas', 'Pulpa y fruta fresca para saborizar.', $jefePlanta);
        $envases = $this->categoria('Envases', 'Botellas, baldes y empaques.', $jefePlanta);
        $derivados = $this->categoria('Derivados lácteos', 'Lo que sale de la planta de Huata.', $jefePlanta);

        // La leche cruda ya vivía en inventory_stocks: se adopta sin perder su saldo.
        // Entra por el caudalímetro de planta y se paga por liquidación semanal,
        // así que queda fuera de la pantalla de Compras: comprarla además la
        // sumaría dos veces al stock y se le pagaría dos veces al productor.
        $leche = $this->insumo($lacteo, [
            'item_code' => 'MILK_RAW_LITERS',
            'name' => 'Leche Fresca Verificada en Planta',
            'measurement_unit_id' => $unidades['L']->id,
            'minimum_stock' => 100,
            'entry_mode' => Supply::ENTRADA_ACOPIO,
        ]);

        $this->sembrarTarifasDeAcopio($leche);

        $this->insumo($cultivos, [
            'item_code' => 'CUAJO_ML',
            'name' => 'Cuajo líquido',
            'measurement_unit_id' => $unidades['ml']->id,
            'unit_cost' => 0.12,
            'minimum_stock' => 200,
            'initial_stock' => 1000,
        ]);

        $this->insumo($cultivos, [
            'item_code' => 'SAL_KG',
            'name' => 'Sal de salmuera',
            'measurement_unit_id' => $unidades['kg']->id,
            'unit_cost' => 1.50,
            'minimum_stock' => 5,
            'initial_stock' => 25,
        ]);

        $cultivoYogurt = $this->insumo($cultivos, [
            'item_code' => 'CULTIVO_YOGURT_G',
            'name' => 'Cultivo para yogurt',
            'measurement_unit_id' => $unidades['g']->id,
            'unit_cost' => 0.80,
            'minimum_stock' => 50,
            'initial_stock' => 200,
        ]);

        $fresa = $this->insumo($frutas, [
            'item_code' => 'FRESA_KG',
            'name' => 'Pulpa de fresa',
            'measurement_unit_id' => $unidades['kg']->id,
            'unit_cost' => 7.50,
            'minimum_stock' => 3,
            'initial_stock' => 12,
        ]);

        $botella = $this->insumo($envases, [
            'item_code' => 'BOTELLA_1L',
            'name' => 'Botella de 1 litro',
            'measurement_unit_id' => $unidades['und']->id,
            'unit_cost' => 0.90,
            'minimum_stock' => 50,
            'initial_stock' => 300,
        ]);

        // El queso de siempre, ahora como dato: 10 L de leche por molde.
        $queso = $this->producto(
            $derivados,
            [
                'item_code' => 'CHEESE_MOLD_UNITS',
                'name' => 'Moldes de Queso Madurado Huata',
                'measurement_unit_id' => $unidades['mold']->id,
                'process_hours' => 12,
                'price_provider' => 18.00,
                'price_wholesale' => 19.00,
                'price_local' => 20.00,
                'process_notes' => 'Cuajado, corte, prensado y maduración mínima de 12 horas.',
            ],
            [
                ['supply_id' => $leche->id, 'quantity_per_unit' => 10, 'notes' => 'Regla histórica de Huata: 10 L por molde.'],
            ],
            $jefePlanta
        );

        // Segundo producto de ejemplo: muestra una receta con varios insumos de
        // distintas categorías (lácteo, fruta, cultivo y envase).
        $this->producto(
            $derivados,
            [
                'item_code' => 'YOGURT_FRESA_1L',
                'name' => 'Yogurt de fresa 1 L',
                'measurement_unit_id' => $unidades['und']->id,
                'process_hours' => 6,
                'price_provider' => 7.00,
                'price_wholesale' => 8.00,
                'price_local' => 9.50,
                'process_notes' => 'Ejemplo: pasteurizar, sembrar cultivo, fermentar 6 h, enfriar y embotellar.',
            ],
            [
                ['supply_id' => $leche->id, 'quantity_per_unit' => 1.05, 'notes' => 'Incluye la merma del envasado.'],
                ['supply_id' => $fresa->id, 'quantity_per_unit' => 0.08],
                ['supply_id' => $cultivoYogurt->id, 'quantity_per_unit' => 5],
                ['supply_id' => $botella->id, 'quantity_per_unit' => 1],
            ],
            $jefePlanta
        );

        // El stock de queso que ya existía no se pierde al adoptarlo en el catálogo.
        InventoryStock::where('item_code', $queso->item_code)
            ->update(['inventory_category_id' => $derivados->id]);
    }

    /** Si la categoría ya existe, se reutiliza en lugar de fallar. */
    private function categoria(string $nombre, ?string $descripcion, ?User $autor): InventoryCategory
    {
        return InventoryCategory::where('slug', Str::slug($nombre))->first()
            ?: $this->catalogo->crearCategoria($nombre, $descripcion, $autor);
    }

    /**
     * Si el insumo ya está dado de alta, se reutiliza tal cual.
     *
     * @param  array{item_code?: string, name: string, unit: string, unit_cost?: float, minimum_stock?: float, initial_stock?: float}  $datos
     */
    private function insumo(InventoryCategory $categoria, array $datos): Supply
    {
        $codigo = $this->codigo($datos);
        $existente = Supply::where('item_code', $codigo)->first();

        if (! $existente) {
            return $this->catalogo->crearInsumo($categoria, $datos);
        }

        // Insumo sembrado antes de que existieran las unidades de medida.
        if (! $existente->measurement_unit_id && isset($datos['measurement_unit_id'])) {
            $existente->update(['measurement_unit_id' => $datos['measurement_unit_id']]);
        }

        // Y antes de que el insumo declarara por dónde entra al almacén.
        if (isset($datos['entry_mode']) && $existente->entry_mode !== $datos['entry_mode']) {
            $existente->update(['entry_mode' => $datos['entry_mode']]);
        }

        return $existente;
    }

    /**
     * Si el producto ya está dado de alta, se reutiliza tal cual.
     *
     * @param  array<string, mixed>  $datos
     * @param  array<int, array{supply_id: int, quantity_per_unit: float, notes?: string|null}>  $receta
     */
    private function producto(InventoryCategory $categoria, array $datos, array $receta, ?User $autor): Product
    {
        $codigo = $this->codigo($datos);

        $existente = Product::where('item_code', $codigo)->first();

        if (! $existente) {
            return $this->catalogo->crearProducto($categoria, $datos, $receta, $autor);
        }

        if (! $existente->measurement_unit_id && isset($datos['measurement_unit_id'])) {
            $existente->update(['measurement_unit_id' => $datos['measurement_unit_id']]);
        }

        return $existente;
    }

    /** @param  array<string, mixed>  $datos */
    private function codigo(array $datos): string
    {
        return strtoupper($datos['item_code'] ?? Str::slug($datos['name'], '_'));
    }

    /**
     * Las tarifas con las que se le paga la leche al productor.
     *
     * Viven aquí y no solo en la migración porque en una instalación nueva el
     * insumo se crea DESPUÉS de migrar: el backfill no lo alcanzaría.
     */
    private function sembrarTarifasDeAcopio(Supply $leche): void
    {
        if (CollectionPriceRule::where('supply_id', $leche->id)->exists()) {
            return;
        }

        $vigentes = SystemPrice::current();

        $reglas = [
            ['Tarifa base', null, null, null, $vigentes->price_milk_base, false, null, 0],
            ['Agua detectada hasta 5%', 'water_addition_percentage', '>', 0, $vigentes->price_milk_water_penalty_low, true, 'leve_descuento', 10],
            ['Agua sobre 5% (riesgo de expulsión)', 'water_addition_percentage', '>', 5, $vigentes->price_milk_water_penalty_high, true, 'grave_expulsion', 20],
        ];

        foreach ($reglas as [$nombre, $metrica, $operador, $umbral, $precio, $ciclo, $penalidad, $prioridad]) {
            CollectionPriceRule::create([
                'supply_id' => $leche->id,
                'name' => $nombre,
                'metric' => $metrica,
                'operator' => $operador,
                'threshold' => $umbral,
                'price_per_unit' => $precio,
                'applies_to_whole_cycle' => $ciclo,
                'penalty_type' => $penalidad,
                'priority' => $prioridad,
                'is_active' => true,
            ]);
        }
    }
}
