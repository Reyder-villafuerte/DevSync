<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\InventoryCategory;
use App\Models\InventoryStock;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\Supply;
use App\Models\User;
use App\Services\Produccion\CatalogoService;
use App\Services\Produccion\ProduccionService;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Un producto terminado puede ser ingrediente de otro.
 *
 * Es lo que hace falta para que la planta pase de «solo queso» a cualquier cosa
 * con etapas: un sándwich que lleva un queso, una masa madre intermedia.
 */
class SemielaboradoTest extends TestCase
{
    use RefreshDatabase;

    private User $jefePlanta;

    private CatalogoService $catalogo;

    private Product $queso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();
        $this->catalogo = app(CatalogoService::class);
        $this->queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
    }

    public function test_un_producto_puede_llevar_otro_producto_en_su_receta(): void
    {
        $sandwich = $this->crearSandwich();

        $renglon = $sandwich->recipeItems->firstWhere('component_product_id', $this->queso->id);

        $this->assertNotNull($renglon);
        $this->assertTrue($renglon->esProducto());
        $this->assertNull($renglon->supply_id);
        $this->assertSame('Moldes de Queso Madurado Huata', $renglon->nombreIngrediente());
    }

    public function test_el_costo_baja_hasta_la_receta_del_componente(): void
    {
        // Leche a S/ 1.40: el molde de queso lleva 10 L, o sea S/ 14 de insumo.
        Supply::where('item_code', 'MILK_RAW_LITERS')->update(['unit_cost' => 1.40]);

        $sandwich = $this->crearSandwich();

        // 0.25 de molde = S/ 3.50 de queso, más 2 unidades de envase a S/ 0.80.
        $this->assertEqualsWithDelta(3.50 + 1.60, $sandwich->fresh()->recipeCost(), 0.001);
    }

    public function test_producir_el_semielaborado_descuenta_el_stock_del_producto_componente(): void
    {
        $sandwich = $this->crearSandwich();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 10);
        InventoryStock::adjustStock('BOTELLA_1L', 100);

        $quesoAntes = InventoryStock::getStock('CHEESE_MOLD_UNITS');
        $produccion = app(ProduccionService::class);

        $orden = $produccion->planificar($sandwich, $this->jefePlanta, 4);
        $produccion->iniciar($orden);

        // 4 sándwiches × 0.25 de molde = 1 molde consumido.
        $this->assertEquals($quesoAntes - 1, InventoryStock::getStock('CHEESE_MOLD_UNITS'));

        $renglon = $orden->fresh('items')->items->firstWhere('component_product_id', $this->queso->id);
        $this->assertNotNull($renglon);
        $this->assertEquals(1, (float) $renglon->quantity_used);
    }

    public function test_cancelar_el_lote_devuelve_el_producto_componente_al_almacen(): void
    {
        $sandwich = $this->crearSandwich();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 10);
        InventoryStock::adjustStock('BOTELLA_1L', 100);

        $quesoAntes = InventoryStock::getStock('CHEESE_MOLD_UNITS');
        $produccion = app(ProduccionService::class);

        $orden = $produccion->planificar($sandwich, $this->jefePlanta, 4);
        $produccion->iniciar($orden);
        $produccion->cancelar($orden->fresh(), 'Se cortó la luz.');

        $this->assertEquals($quesoAntes, InventoryStock::getStock('CHEESE_MOLD_UNITS'));
    }

    public function test_no_se_puede_iniciar_el_lote_sin_stock_del_componente(): void
    {
        $sandwich = $this->crearSandwich();
        InventoryStock::adjustStock('BOTELLA_1L', 100);

        // 400 sándwiches piden 100 moldes y en almacén hay bastante menos.
        $produccion = app(ProduccionService::class);
        $orden = $produccion->planificar($sandwich, $this->jefePlanta, 400);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('Falta Moldes de Queso Madurado Huata');

        $produccion->iniciar($orden);
    }

    public function test_un_producto_no_puede_llevarse_a_si_mismo(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('no puede llevarse a sí mismo');

        $this->catalogo->reemplazarReceta($this->queso, [
            ['component_product_id' => $this->queso->id, 'quantity_per_unit' => 1],
        ]);
    }

    public function test_dos_productos_no_pueden_llevarse_el_uno_al_otro(): void
    {
        $sandwich = $this->crearSandwich();

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('se harían circulares');

        // El queso no puede llevar el sándwich, porque el sándwich ya lleva queso.
        $this->catalogo->reemplazarReceta($this->queso, [
            ['component_product_id' => $sandwich->id, 'quantity_per_unit' => 1],
        ]);
    }

    public function test_un_renglon_no_puede_ser_insumo_y_producto_a_la_vez(): void
    {
        $leche = Supply::where('item_code', 'MILK_RAW_LITERS')->firstOrFail();

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('es un insumo o un producto, no los dos');

        $this->catalogo->reemplazarReceta($this->queso, [
            [
                'supply_id' => $leche->id,
                'component_product_id' => $this->queso->id,
                'quantity_per_unit' => 1,
            ],
        ]);
    }

    public function test_la_pantalla_de_recetas_ofrece_productos_como_ingrediente(): void
    {
        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.productos.index'))
            ->assertOk()
            ->assertSee('Productos de la planta')
            ->assertSee('Insumos del almacén');
    }

    public function test_la_tabla_de_productos_pagina_busca_y_filtra(): void
    {
        $this->crearSandwich();

        // Buscando por nombre queda un solo producto.
        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.productos.index', ['buscar' => 'Sándwich']))
            ->assertOk()
            ->assertViewHas('productos', fn ($productos) => $productos->total() === 1
                && $productos->first()->item_code === 'SANDWICH_QUESO');

        // Y el filtro por estado deja fuera a los desactivados.
        Product::where('item_code', 'SANDWICH_QUESO')->update(['is_active' => false]);

        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.productos.index', ['estado' => 'activos']))
            ->assertOk()
            ->assertViewHas('productos', fn ($productos) => ! collect($productos->items())
                ->contains('item_code', 'SANDWICH_QUESO'));
    }

    public function test_cada_producto_trae_su_modal_de_receta_y_tarifas(): void
    {
        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.productos.index'))
            ->assertOk()
            ->assertSee('modalProducto'.$this->queso->id, false)
            ->assertSee('Guardar receta')
            ->assertSee('Guardar tarifas');
    }

    /** Un producto de ejemplo que lleva un cuarto de molde de queso y dos envases. */
    private function crearSandwich(): Product
    {
        $categoria = InventoryCategory::where('slug', 'derivados-lacteos')->firstOrFail();
        $envase = Supply::where('item_code', 'BOTELLA_1L')->firstOrFail();
        $envase->update(['unit_cost' => 0.80]);

        return $this->catalogo->crearProducto(
            $categoria,
            [
                'item_code' => 'SANDWICH_QUESO',
                'name' => 'Sándwich de queso',
                'measurement_unit_id' => MeasurementUnit::where('abbreviation', 'und')->firstOrFail()->id,
                'process_hours' => 0,
                'price_local' => 8.00,
            ],
            [
                ['component_product_id' => $this->queso->id, 'quantity_per_unit' => 0.25],
                ['supply_id' => $envase->id, 'quantity_per_unit' => 2],
            ],
            $this->jefePlanta
        );
    }
}
