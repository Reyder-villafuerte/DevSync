<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\ClientType;
use App\Models\Customer;
use App\Models\InventoryCategory;
use App\Models\InventoryStock;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\Supply;
use App\Models\SupplyMovement;
use App\Models\User;
use App\Services\Produccion\CatalogoService;
use App\Services\Produccion\ProduccionService;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El catálogo abierto: la planta da de alta sus propias categorías, insumos y
 * productos, y produce por lotes que tardan lo que dice la receta.
 */
class CatalogoProduccionTest extends TestCase
{
    use RefreshDatabase;

    private User $jefePlanta;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);
        $this->jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();
    }

    public function test_el_queso_queda_sembrado_como_producto_con_su_receta_de_10_litros(): void
    {
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();

        $this->assertSame('mold', $queso->unit);
        $this->assertSame('Moldes', $queso->measurementUnit->name);
        $this->assertEquals(18.00, (float) $queso->price_provider);
        $this->assertEquals(19.00, (float) $queso->price_wholesale);
        $this->assertEquals(20.00, (float) $queso->price_local);

        $receta = $queso->recipeItems()->with('supply')->get();
        $this->assertCount(1, $receta);
        $this->assertSame('MILK_RAW_LITERS', $receta->first()->supply->item_code);
        $this->assertEquals(10.0, (float) $receta->first()->quantity_per_unit);
    }

    public function test_la_planta_crea_su_categoria_insumo_y_producto_nuevos(): void
    {
        $catalogo = app(CatalogoService::class);

        $categoria = $catalogo->crearCategoria('Bebidas fermentadas', null, $this->jefePlanta);
        $leche = Supply::where('item_code', 'MILK_RAW_LITERS')->firstOrFail();
        $fresa = Supply::where('item_code', 'FRESA_KG')->firstOrFail();
        $botella = Supply::where('item_code', 'BOTELLA_1L')->firstOrFail();

        $yogurt = $catalogo->crearProducto(
            $categoria,
            [
                'name' => 'Yogurt de fresa 1 L',
                'unit' => 'botellas',
                'process_hours' => 6,
                'price_provider' => 7.00,
                'price_wholesale' => 8.00,
                'price_local' => 9.50,
            ],
            [
                ['supply_id' => $leche->id, 'quantity_per_unit' => 1.05],
                ['supply_id' => $fresa->id, 'quantity_per_unit' => 0.08],
                ['supply_id' => $botella->id, 'quantity_per_unit' => 1],
            ],
            $this->jefePlanta
        );

        $this->assertSame('YOGURT_DE_FRESA_1_L', $yogurt->item_code);
        $this->assertCount(3, $yogurt->recipeItems);
        $this->assertDatabaseHas('inventory_stocks', [
            'item_code' => 'YOGURT_DE_FRESA_1_L',
            'inventory_category_id' => $categoria->id,
        ]);
    }

    public function test_un_producto_sin_receta_no_se_puede_crear(): void
    {
        $categoria = InventoryCategory::where('slug', 'derivados-lacteos')->firstOrFail();

        $this->expectException(ReglaNegocioException::class);

        app(CatalogoService::class)->crearProducto(
            $categoria,
            ['name' => 'Mantequilla sin receta', 'unit' => 'kilos'],
            []
        );
    }

    public function test_el_lote_descuenta_los_insumos_al_iniciar_y_entrega_el_producto_al_terminar(): void
    {
        $produccion = app(ProduccionService::class);
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();

        $lecheAntes = InventoryStock::getStock('MILK_RAW_LITERS');
        $quesoAntes = InventoryStock::getStock('CHEESE_MOLD_UNITS');

        $orden = $produccion->planificar($queso, $this->jefePlanta, 5);
        $this->assertSame('planificada', $orden->status);
        $this->assertEquals($lecheAntes, InventoryStock::getStock('MILK_RAW_LITERS'), 'Planificar no debe tocar el almacén.');

        $orden = $produccion->iniciar($orden);
        $this->assertSame('en_proceso', $orden->status);
        $this->assertEquals($lecheAntes - 50, InventoryStock::getStock('MILK_RAW_LITERS'));
        $this->assertEquals($quesoAntes, InventoryStock::getStock('CHEESE_MOLD_UNITS'), 'El producto entra recién al cerrar el lote.');

        $this->travel(13)->hours();

        $orden = $produccion->terminar($orden->fresh());
        $this->assertSame('terminada', $orden->status);
        $this->assertEquals($quesoAntes + 5, InventoryStock::getStock('CHEESE_MOLD_UNITS'));
    }

    public function test_el_lote_no_se_puede_cerrar_antes_de_que_el_proceso_cumpla_su_tiempo(): void
    {
        $produccion = app(ProduccionService::class);
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();

        $orden = $produccion->iniciar($produccion->planificar($queso, $this->jefePlanta, 2));

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessageMatches('/todavía no termina/');

        $produccion->terminar($orden);
    }

    public function test_no_se_inicia_un_lote_sin_insumos_suficientes(): void
    {
        $produccion = app(ProduccionService::class);
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
        $lecheDisponible = InventoryStock::getStock('MILK_RAW_LITERS');

        $orden = $produccion->planificar($queso, $this->jefePlanta, ($lecheDisponible / 10) + 1);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessageMatches('/Falta Leche/');

        $produccion->iniciar($orden);
    }

    public function test_cancelar_un_lote_en_proceso_devuelve_los_insumos(): void
    {
        $produccion = app(ProduccionService::class);
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
        $lecheAntes = InventoryStock::getStock('MILK_RAW_LITERS');

        $orden = $produccion->iniciar($produccion->planificar($queso, $this->jefePlanta, 4));
        $this->assertEquals($lecheAntes - 40, InventoryStock::getStock('MILK_RAW_LITERS'));

        $produccion->cancelar($orden, 'Corte de energía en planta');

        $this->assertEquals($lecheAntes, InventoryStock::getStock('MILK_RAW_LITERS'));
        $this->assertSame('cancelada', $orden->fresh()->status);
    }

    public function test_el_jefe_de_planta_crea_un_producto_desde_la_web(): void
    {
        $categoria = InventoryCategory::where('slug', 'derivados-lacteos')->firstOrFail();
        $leche = Supply::where('item_code', 'MILK_RAW_LITERS')->firstOrFail();

        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.productos.store'), [
                'inventory_category_id' => $categoria->id,
                'name' => 'Mantequilla artesanal',
                'measurement_unit_id' => MeasurementUnit::where('abbreviation', 'kg')->firstOrFail()->id,
                'process_hours' => 3,
                'tarifas' => [
                    ['client_type_id' => ClientType::where('slug', 'proveedor')->value('id'), 'price_per_unit' => 22],
                    ['client_type_id' => ClientType::where('slug', 'mayorista')->value('id'), 'price_per_unit' => 24],
                    ['client_type_id' => ClientType::where('slug', 'local')->value('id'), 'price_per_unit' => 26],
                ],
                'receta' => [
                    ['ingrediente' => 'i:'.$leche->id, 'quantity_per_unit' => 20],
                ],
            ])
            ->assertRedirect(route('produccion.productos.index'))
            ->assertSessionHas('success');

        $producto = Product::where('name', 'Mantequilla artesanal')->firstOrFail();
        $this->assertEquals(20.0, (float) $producto->recipeItems()->firstOrFail()->quantity_per_unit);
    }

    public function test_la_web_crea_categoria_e_insumo_y_ajusta_el_almacen(): void
    {
        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.categorias.store'), ['name' => 'Empaques secundarios'])
            ->assertSessionHas('success');

        $categoria = InventoryCategory::where('slug', 'empaques-secundarios')->firstOrFail();

        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.almacen.insumos.store'), [
                'inventory_category_id' => $categoria->id,
                'name' => 'Caja de cartón',
                'measurement_unit_id' => MeasurementUnit::where('abbreviation', 'und')->firstOrFail()->id,
                'initial_stock' => 40,
                'minimum_stock' => 10,
            ])
            ->assertSessionHas('success');

        $insumo = Supply::where('item_code', 'CAJA_DE_CARTON')->firstOrFail();
        $this->assertEquals(40.0, $insumo->stock());

        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.almacen.insumos.ajuste', $insumo), ['delta' => -15])
            ->assertSessionHas('success');

        $this->assertEquals(25.0, $insumo->fresh()->stock());
    }

    public function test_el_acopiador_no_entra_al_catalogo_de_la_planta(): void
    {
        $acopiador = User::where('role', 'acopiador')->firstOrFail();

        $this->actingAs($acopiador)
            ->get(route('produccion.categorias.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($acopiador)
            ->get(route('produccion.almacen.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($acopiador)
            ->get(route('produccion.lotes.index'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_la_web_muestra_el_catalogo_y_los_lotes(): void
    {
        $produccion = app(ProduccionService::class);
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
        $produccion->iniciar($produccion->planificar($queso, $this->jefePlanta, 3));

        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.categorias.index'))
            ->assertOk()
            ->assertSee('El vocabulario del almacén')
            ->assertSee('Lácteo base');

        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.almacen.index'))
            ->assertOk()
            ->assertSee('Almacén e Insumos')
            ->assertSee('Botella de 1 litro');

        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.productos.index'))
            ->assertOk()
            ->assertSee('Productos y Recetas')
            ->assertSee('Moldes de Queso Madurado Huata');

        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.lotes.index'))
            ->assertOk()
            ->assertSee('Lotes de Producción')
            ->assertSee(ProductionOrder::where('status', 'en_proceso')->firstOrFail()->batch_number);
    }

    public function test_el_precio_depende_del_tipo_de_comprador(): void
    {
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();

        $proveedor = Customer::where('type', 'proveedor')->firstOrFail();
        $mayorista = Customer::where('type', 'mayorista')->firstOrFail();
        $local = Customer::where('type', 'local')->firstOrFail();

        $this->assertEquals(18.00, $queso->priceForCustomer($proveedor));
        $this->assertEquals(19.00, $queso->priceForCustomer($mayorista));
        $this->assertEquals(20.00, $queso->priceForCustomer($local));
        $this->assertEquals(19.00, $queso->priceForCustomer($local, 12), 'Doce unidades ya son compra mayorista.');
    }

    public function test_la_planta_agrega_su_propia_unidad_de_medida(): void
    {
        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.categorias.unidades.store'), [
                'name' => 'Baldes de 20 litros',
                'abbreviation' => 'balde',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('measurement_units', ['abbreviation' => 'balde']);

        // La abreviatura es única: no se puede repetir.
        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.categorias.unidades.store'), [
                'name' => 'Otro balde',
                'abbreviation' => 'balde',
            ])
            ->assertSessionHasErrors('abbreviation');
    }

    public function test_el_kardex_registra_ingreso_consumo_y_devolucion(): void
    {
        $produccion = app(ProduccionService::class);
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
        $leche = Supply::where('item_code', 'MILK_RAW_LITERS')->firstOrFail();

        $orden = $produccion->iniciar($produccion->planificar($queso, $this->jefePlanta, 3));

        $consumo = SupplyMovement::where('supply_id', $leche->id)
            ->where('production_order_id', $orden->id)
            ->where('type', 'consumo_produccion')
            ->firstOrFail();

        $this->assertEquals(-30, (float) $consumo->quantity);
        $this->assertEquals(InventoryStock::getStock('MILK_RAW_LITERS'), (float) $consumo->balance_after);
        $this->assertSame($this->jefePlanta->id, $consumo->registered_by);

        $produccion->cancelar($orden, 'Prueba');

        $devolucion = SupplyMovement::where('production_order_id', $orden->id)
            ->where('type', 'devolucion')
            ->firstOrFail();

        $this->assertEquals(30, (float) $devolucion->quantity);

        // El alta de un insumo con stock inicial también deja su ingreso.
        $this->assertDatabaseHas('supply_movements', [
            'supply_id' => Supply::where('item_code', 'BOTELLA_1L')->firstOrFail()->id,
            'type' => 'ingreso',
            'quantity' => 300,
        ]);
    }

    public function test_el_ajuste_manual_queda_en_el_kardex_con_su_motivo(): void
    {
        $fresa = Supply::where('item_code', 'FRESA_KG')->firstOrFail();

        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.almacen.insumos.ajuste', $fresa), [
                'delta' => -2,
                'notes' => 'Fruta malograda en cámara',
            ])
            ->assertSessionHas('success');

        $movimiento = SupplyMovement::where('supply_id', $fresa->id)
            ->where('type', 'ajuste')
            ->latest('id')
            ->firstOrFail();

        $this->assertEquals(-2, (float) $movimiento->quantity);
        $this->assertSame('Fruta malograda en cámara', $movimiento->notes);
        $this->assertEquals(10.0, (float) $movimiento->balance_after);
    }

    public function test_se_puede_editar_una_categoria_desde_la_tabla(): void
    {
        $categoria = InventoryCategory::where('slug', 'frutas')->firstOrFail();

        $this->actingAs($this->jefePlanta)
            ->put(route('produccion.categorias.update', $categoria), [
                'name' => 'Frutas y pulpas',
                'description' => 'Fruta fresca y pulpa congelada.',
                'is_active' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('inventory_categories', [
            'id' => $categoria->id,
            'name' => 'Frutas y pulpas',
            'slug' => 'frutas-y-pulpas',
        ]);
    }

    public function test_no_se_elimina_una_categoria_que_tiene_insumos(): void
    {
        $categoria = InventoryCategory::where('slug', 'envases')->firstOrFail();

        $this->actingAs($this->jefePlanta)
            ->delete(route('produccion.categorias.destroy', $categoria))
            ->assertSessionHasErrors('categoria');

        $this->assertDatabaseHas('inventory_categories', ['id' => $categoria->id]);
    }

    public function test_se_elimina_una_categoria_vacia(): void
    {
        $categoria = app(CatalogoService::class)->crearCategoria('Etiquetas', null, $this->jefePlanta);

        $this->actingAs($this->jefePlanta)
            ->delete(route('produccion.categorias.destroy', $categoria))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('inventory_categories', ['id' => $categoria->id]);
    }

    public function test_editar_una_unidad_actualiza_la_abreviatura_de_sus_insumos(): void
    {
        $unidad = MeasurementUnit::where('abbreviation', 'kg')->firstOrFail();

        $this->actingAs($this->jefePlanta)
            ->put(route('produccion.categorias.unidades.update', $unidad), [
                'name' => 'Kilos',
                'abbreviation' => 'Kg',
            ])
            ->assertSessionHas('success');

        $this->assertSame('Kg', Supply::where('item_code', 'FRESA_KG')->firstOrFail()->unit);
    }

    public function test_no_se_elimina_una_unidad_en_uso(): void
    {
        $unidad = MeasurementUnit::where('abbreviation', 'L')->firstOrFail();

        $this->actingAs($this->jefePlanta)
            ->delete(route('produccion.categorias.unidades.destroy', $unidad))
            ->assertSessionHasErrors('unidad');

        $this->assertDatabaseHas('measurement_units', ['id' => $unidad->id]);
    }

    public function test_se_elimina_una_unidad_que_nadie_usa(): void
    {
        $unidad = app(CatalogoService::class)->crearUnidadMedida('Cajas', 'caja');

        $this->actingAs($this->jefePlanta)
            ->delete(route('produccion.categorias.unidades.destroy', $unidad))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('measurement_units', ['id' => $unidad->id]);
    }

    public function test_la_pagina_de_categorias_trae_buscador_filtro_y_acciones(): void
    {
        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.categorias.index'))
            ->assertOk()
            ->assertSee('Crear categoría')
            ->assertSee('Crear unidad')
            ->assertSee('Buscar categoría...')
            ->assertSee('Buscar unidad...')
            ->assertSee('Editar')
            ->assertSee('Eliminar');
    }

    public function test_una_categoria_inactiva_deja_de_ofrecerse_al_dar_de_alta(): void
    {
        $categoria = InventoryCategory::where('slug', 'frutas')->firstOrFail();

        $this->actingAs($this->jefePlanta)
            ->put(route('produccion.categorias.update', $categoria), [
                'name' => $categoria->name,
                'description' => $categoria->description,
            ])
            ->assertSessionHas('success');

        $this->assertFalse($categoria->fresh()->is_active);

        $ofrecidas = $this->actingAs($this->jefePlanta)
            ->get(route('produccion.productos.index'))
            ->assertOk()
            ->viewData('categorias');

        $this->assertNotContains($categoria->id, $ofrecidas->pluck('id'));
    }
}
