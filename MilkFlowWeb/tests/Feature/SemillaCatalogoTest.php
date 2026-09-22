<?php

namespace Tests\Feature;

use App\Models\InventoryCategory;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\Supply;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo que debe quedar en pie después de `php artisan migrate:fresh --seed`.
 *
 * Siembra por `DatabaseSeeder`, que es exactamente lo que corre ese comando.
 */
class SemillaCatalogoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_las_unidades_de_medida_vienen_por_defecto(): void
    {
        $esperadas = ['L', 'ml', 'g', 'kg', 'und', 'mold'];

        foreach ($esperadas as $abreviatura) {
            $this->assertDatabaseHas('measurement_units', [
                'abbreviation' => $abreviatura,
                'is_active' => true,
            ]);
        }

        $this->assertSame(count($esperadas), MeasurementUnit::count());
    }

    public function test_las_categorias_vienen_sembradas_y_activas(): void
    {
        $categorias = InventoryCategory::where('is_active', true)->pluck('name');

        $this->assertContains('Lácteo base', $categorias);
        $this->assertContains('Envases', $categorias);
        $this->assertContains('Frutas', $categorias);
        $this->assertContains('Cultivos e insumos', $categorias);
        $this->assertContains('Derivados lácteos', $categorias);
    }

    public function test_cada_insumo_sembrado_trae_categoria_unidad_y_stock(): void
    {
        $insumos = Supply::with(['category', 'measurementUnit'])->get();

        $this->assertGreaterThanOrEqual(6, $insumos->count());

        foreach ($insumos as $insumo) {
            $this->assertNotNull($insumo->category, "El insumo {$insumo->name} quedó sin categoría.");
            $this->assertNotNull($insumo->measurementUnit, "El insumo {$insumo->name} quedó sin unidad de medida.");
            $this->assertDatabaseHas('inventory_stocks', ['item_code' => $insumo->item_code]);
        }

        $botella = Supply::where('item_code', 'BOTELLA_1L')->firstOrFail();
        $this->assertSame('Envases', $botella->category->name);
        $this->assertSame('und', $botella->measurementUnit->abbreviation);
        $this->assertEquals(300.0, $botella->stock());
    }

    public function test_el_producto_sembrado_conserva_su_receta_y_sus_tres_precios(): void
    {
        $queso = Product::with(['recipeItems.supply', 'measurementUnit'])
            ->where('item_code', 'CHEESE_MOLD_UNITS')
            ->firstOrFail();

        $this->assertSame('mold', $queso->measurementUnit->abbreviation);
        $this->assertEquals(12.0, (float) $queso->process_hours);
        $this->assertEquals([18.00, 19.00, 20.00], [
            (float) $queso->price_provider,
            (float) $queso->price_wholesale,
            (float) $queso->price_local,
        ]);

        $receta = $queso->recipeItems;
        $this->assertCount(1, $receta);
        $this->assertSame('MILK_RAW_LITERS', $receta->first()->supply->item_code);
        $this->assertEquals(10.0, (float) $receta->first()->quantity_per_unit);
    }

    public function test_volver_a_sembrar_no_duplica_el_catalogo(): void
    {
        $categorias = InventoryCategory::count();
        $insumos = Supply::count();
        $productos = Product::count();
        $unidades = MeasurementUnit::count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($categorias, InventoryCategory::count());
        $this->assertSame($insumos, Supply::count());
        $this->assertSame($productos, Product::count());
        $this->assertSame($unidades, MeasurementUnit::count());
    }

    public function test_el_yogurt_de_ejemplo_muestra_una_receta_con_varias_categorias(): void
    {
        $yogurt = Product::with('recipeItems.supply.category')
            ->where('item_code', 'YOGURT_FRESA_1L')
            ->firstOrFail();

        $categorias = $yogurt->recipeItems->map(fn ($renglon) => $renglon->supply->category->name);

        $this->assertCount(4, $yogurt->recipeItems);
        $this->assertContains('Lácteo base', $categorias);
        $this->assertContains('Frutas', $categorias);
        $this->assertContains('Envases', $categorias);
        $this->assertContains('Cultivos e insumos', $categorias);
    }
}
