<?php

namespace Tests\Feature;

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
 * El almacén distingue lo que se compra de lo que se vende.
 *
 * Un insumo (envase, cuajo) tiene costo y nunca precio de venta; un producto
 * terminado (queso, yogurt) entra por producción y lleva sus tres tarifas.
 */
class AlmacenClasificadoTest extends TestCase
{
    use RefreshDatabase;

    private User $jefePlanta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);
        $this->jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();
    }

    public function test_la_semilla_deja_cada_fila_de_stock_sabiendo_si_es_insumo_o_producto(): void
    {
        $leche = InventoryStock::where('item_code', 'MILK_RAW_LITERS')->firstOrFail();
        $queso = InventoryStock::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();

        $this->assertTrue($leche->esInsumo());
        $this->assertSame(Supply::where('item_code', 'MILK_RAW_LITERS')->value('id'), $leche->supply_id);
        $this->assertNull($leche->product_id);

        $this->assertTrue($queso->esProducto());
        $this->assertSame(Product::where('item_code', 'CHEESE_MOLD_UNITS')->value('id'), $queso->product_id);
        $this->assertNull($queso->supply_id);

        $this->assertSame(0, InventoryStock::whereNull('kind')->count());
    }

    public function test_un_insumo_nuevo_nace_clasificado_como_insumo(): void
    {
        $categoria = InventoryCategory::where('slug', 'envases')->firstOrFail();
        $unidad = MeasurementUnit::where('abbreviation', 'und')->firstOrFail();

        $insumo = app(CatalogoService::class)->crearInsumo($categoria, [
            'name' => 'Tapa rosca 38 mm',
            'measurement_unit_id' => $unidad->id,
            'unit_cost' => 0.15,
            'initial_stock' => 400,
        ]);

        $stock = InventoryStock::where('item_code', $insumo->item_code)->firstOrFail();

        $this->assertTrue($stock->esInsumo());
        $this->assertSame($insumo->id, $stock->supply_id);
        $this->assertEquals(400, (float) $stock->current_stock);
    }

    public function test_el_producto_terminado_entra_al_almacen_marcado_como_producto(): void
    {
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
        $produccion = app(ProduccionService::class);

        $orden = $produccion->planificar($queso, $this->jefePlanta, 3);
        $produccion->iniciar($orden);

        // El queso madura: para cerrar el lote hay que estar pasado su tiempo.
        $orden->forceFill(['expected_ready_at' => now()->subMinute()])->save();
        $produccion->terminar($orden->fresh());

        $stock = InventoryStock::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();

        $this->assertTrue($stock->esProducto());
        $this->assertSame($queso->id, $stock->product_id);
        $this->assertNull($stock->supply_id);
    }

    public function test_el_costo_de_receta_del_queso_sale_de_los_diez_litros_de_leche(): void
    {
        $leche = Supply::where('item_code', 'MILK_RAW_LITERS')->firstOrFail();
        $leche->update(['unit_cost' => 1.40]);

        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail()->fresh();

        // 10 L a S/ 1.40 = S/ 14.00 de insumo por molde, contra S/ 20.00 local.
        $this->assertEqualsWithDelta(14.00, $queso->recipeCost(), 0.001);
        $this->assertEqualsWithDelta(6.00, $queso->localMargin(), 0.001);
    }

    public function test_la_pantalla_de_almacen_separa_lo_que_se_compra_de_lo_que_se_vende(): void
    {
        $respuesta = $this->actingAs($this->jefePlanta)->get(route('produccion.almacen.index'));

        $respuesta->assertOk();
        $respuesta->assertViewHas('insumos', fn ($insumos) => $insumos->contains('item_code', 'BOTELLA_1L')
            && ! $insumos->contains('item_code', 'CHEESE_MOLD_UNITS'));
        $respuesta->assertViewHas('productos', fn ($productos) => $productos->contains('item_code', 'CHEESE_MOLD_UNITS')
            && ! $productos->contains('item_code', 'BOTELLA_1L'));

        $respuesta->assertSee('Productos terminados');
        $respuesta->assertSee('Margen local');
    }
}
