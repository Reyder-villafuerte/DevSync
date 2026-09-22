<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Supply;
use App\Models\SupplyMovement;
use App\Models\User;
use App\Services\Produccion\CatalogoService;
use App\Services\Produccion\ComprasService;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Compras de insumos: el origen del costo del almacén.
 *
 * Una compra no es una fila muerta: mueve el stock al guardarse, y corregirla o
 * anularla tiene que devolver esa mercadería sin dejar el almacén en negativo.
 */
class ComprasInsumosTest extends TestCase
{
    use RefreshDatabase;

    private User $jefePlanta;

    private Supplier $proveedor;

    private Supply $botella;

    private ComprasService $compras;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();
        $this->compras = app(ComprasService::class);
        $this->botella = Supply::where('item_code', 'BOTELLA_1L')->firstOrFail();

        $this->proveedor = $this->compras->crearProveedor([
            'name' => 'Envases del Altiplano SAC',
            'document' => '20456789012',
        ], $this->jefePlanta);
    }

    public function test_registrar_una_compra_ingresa_el_stock_y_deja_su_kardex(): void
    {
        $inicial = $this->botella->stock();

        $compra = $this->compras->registrar($this->datosCompra([
            ['supply_id' => $this->botella->id, 'quantity' => 100, 'unit_cost' => 1.00],
        ]), $this->jefePlanta);

        $this->assertEquals(100.00, (float) $compra->total_amount);
        $this->assertEquals($inicial + 100, $this->botella->fresh()->stock());

        $movimiento = SupplyMovement::where('purchase_id', $compra->id)->firstOrFail();
        $this->assertSame('compra', $movimiento->type);
        $this->assertEquals(100, (float) $movimiento->quantity);
        $this->assertEquals(1.00, (float) $movimiento->unit_cost);
    }

    public function test_el_costo_del_insumo_es_el_promedio_ponderado_de_sus_compras(): void
    {
        $this->compras->registrar($this->datosCompra([
            ['supply_id' => $this->botella->id, 'quantity' => 100, 'unit_cost' => 1.00],
        ]), $this->jefePlanta);

        $this->compras->registrar($this->datosCompra([
            ['supply_id' => $this->botella->id, 'quantity' => 300, 'unit_cost' => 1.20],
        ]), $this->jefePlanta);

        // (100 * 1.00 + 300 * 1.20) / 400 = 1.15
        $this->assertEquals(1.15, (float) $this->botella->fresh()->unit_cost);
    }

    public function test_corregir_una_compra_devuelve_lo_viejo_y_aplica_lo_nuevo(): void
    {
        $inicial = $this->botella->stock();

        $compra = $this->compras->registrar($this->datosCompra([
            ['supply_id' => $this->botella->id, 'quantity' => 100, 'unit_cost' => 1.00],
        ]), $this->jefePlanta);

        $this->compras->actualizar($compra, $this->datosCompra([
            ['supply_id' => $this->botella->id, 'quantity' => 50, 'unit_cost' => 2.00],
        ]), $this->jefePlanta);

        $this->assertEquals($inicial + 50, $this->botella->fresh()->stock());
        $this->assertEquals(100.00, (float) $compra->fresh()->total_amount);
        $this->assertEquals(2.00, (float) $this->botella->fresh()->unit_cost);
        $this->assertCount(1, $compra->fresh()->items);
    }

    public function test_anular_una_compra_devuelve_su_mercaderia_al_almacen(): void
    {
        $inicial = $this->botella->stock();

        $compra = $this->compras->registrar($this->datosCompra([
            ['supply_id' => $this->botella->id, 'quantity' => 100, 'unit_cost' => 1.00],
        ]), $this->jefePlanta);

        $this->compras->eliminar($compra, $this->jefePlanta);

        $this->assertEquals($inicial, $this->botella->fresh()->stock());
        $this->assertDatabaseMissing('purchases', ['id' => $compra->id]);

        // El kardex conserva la entrada y su anulación: no se borra historia.
        $this->assertSame(1, SupplyMovement::where('type', 'compra')->count());
        $this->assertSame(1, SupplyMovement::where('type', 'anulacion_compra')->count());
    }

    public function test_no_se_puede_corregir_una_compra_cuya_mercaderia_ya_se_consumio(): void
    {
        $compra = $this->compras->registrar($this->datosCompra([
            ['supply_id' => $this->botella->id, 'quantity' => 100, 'unit_cost' => 1.00],
        ]), $this->jefePlanta);

        // La planta usa casi todo lo que había, incluida la compra.
        app(CatalogoService::class)->ajustarStockInsumo(
            $this->botella->fresh(),
            -350,
            $this->jefePlanta,
            'Consumo de la semana.'
        );

        $quedan = $this->botella->fresh()->stock();

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('ya se consumió');

        try {
            $this->compras->eliminar($compra, $this->jefePlanta);
        } finally {
            // El almacén no se tocó: el rechazo es antes de mover nada.
            $this->assertEquals($quedan, $this->botella->fresh()->stock());
        }
    }

    public function test_la_jefatura_registra_corrige_y_anula_una_compra_desde_la_web(): void
    {
        $inicial = $this->botella->stock();

        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.compras.store'), $this->datosCompra([
                ['supply_id' => $this->botella->id, 'quantity' => 40, 'unit_cost' => 1.50],
            ]))
            ->assertSessionHasNoErrors();

        $compra = Purchase::firstOrFail();
        $this->assertEquals($inicial + 40, $this->botella->fresh()->stock());

        $this->actingAs($this->jefePlanta)
            ->put(route('produccion.compras.update', $compra), $this->datosCompra([
                ['supply_id' => $this->botella->id, 'quantity' => 60, 'unit_cost' => 1.50],
            ]))
            ->assertSessionHasNoErrors();

        $this->assertEquals($inicial + 60, $this->botella->fresh()->stock());

        $this->actingAs($this->jefePlanta)
            ->delete(route('produccion.compras.destroy', $compra))
            ->assertSessionHasNoErrors();

        $this->assertEquals($inicial, $this->botella->fresh()->stock());
    }

    public function test_una_compra_puede_llevar_varios_insumos(): void
    {
        $cuajo = Supply::where('item_code', 'CUAJO_ML')->firstOrFail();
        $botellaAntes = $this->botella->stock();
        $cuajoAntes = $cuajo->stock();

        $compra = $this->compras->registrar($this->datosCompra([
            ['supply_id' => $this->botella->id, 'quantity' => 100, 'unit_cost' => 1.00],
            ['supply_id' => $cuajo->id, 'quantity' => 500, 'unit_cost' => 0.12],
        ]), $this->jefePlanta);

        $this->assertCount(2, $compra->items);
        $this->assertEquals(160.00, (float) $compra->total_amount);
        $this->assertEquals($botellaAntes + 100, $this->botella->fresh()->stock());
        $this->assertEquals($cuajoAntes + 500, $cuajo->fresh()->stock());
    }

    public function test_se_puede_escribir_el_proveedor_de_la_boleta_sin_haberlo_fichado_antes(): void
    {
        $compra = $this->compras->registrar([
            'purchase_date' => now()->format('Y-m-d'),
            'document_number' => 'B002-00044',
            'new_supplier_name' => 'Ferretería Titicaca EIRL',
            'new_supplier_document' => '20777888999',
            'items' => [
                ['supply_id' => $this->botella->id, 'quantity' => 10, 'unit_cost' => 1.00],
            ],
        ], $this->jefePlanta);

        $this->assertSame('Ferretería Titicaca EIRL', $compra->supplier->name);
        $this->assertSame('20777888999', $compra->supplier->document);
        $this->assertDatabaseHas('suppliers', ['document' => '20777888999']);
    }

    public function test_el_mismo_ruc_no_crea_un_proveedor_repetido(): void
    {
        $fichados = Supplier::count();

        // El proveedor del setUp ya tiene este RUC.
        $compra = $this->compras->registrar([
            'purchase_date' => now()->format('Y-m-d'),
            'new_supplier_name' => 'Envases Altiplano (como vino en la boleta)',
            'new_supplier_document' => '20456789012',
            'items' => [
                ['supply_id' => $this->botella->id, 'quantity' => 5, 'unit_cost' => 1.00],
            ],
        ], $this->jefePlanta);

        $this->assertSame($this->proveedor->id, $compra->supplier_id);
        $this->assertSame($fichados, Supplier::count());
    }

    public function test_el_mismo_nombre_tampoco_duplica_aunque_cambien_las_mayusculas(): void
    {
        $fichados = Supplier::count();

        $compra = $this->compras->registrar([
            'purchase_date' => now()->format('Y-m-d'),
            'new_supplier_name' => 'ENVASES DEL ALTIPLANO SAC',
            'items' => [
                ['supply_id' => $this->botella->id, 'quantity' => 5, 'unit_cost' => 1.00],
            ],
        ], $this->jefePlanta);

        $this->assertSame($this->proveedor->id, $compra->supplier_id);
        $this->assertSame($fichados, Supplier::count());
    }

    public function test_una_compra_sin_nombre_en_la_boleta_se_rechaza(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('Indica a quién se le compró');

        $this->compras->registrar([
            'purchase_date' => now()->format('Y-m-d'),
            'items' => [
                ['supply_id' => $this->botella->id, 'quantity' => 5, 'unit_cost' => 1.00],
            ],
        ], $this->jefePlanta);
    }

    public function test_la_pantalla_de_compras_muestra_el_historial(): void
    {
        $this->compras->registrar($this->datosCompra([
            ['supply_id' => $this->botella->id, 'quantity' => 25, 'unit_cost' => 1.10],
        ]), $this->jefePlanta);

        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.compras.index'))
            ->assertOk()
            ->assertSee('Compras de insumos')
            ->assertSee('Envases del Altiplano SAC')
            ->assertSee('Botella de 1 litro')
            // El padrón de proveedores se quitó: el nombre se escribe en la boleta.
            ->assertDontSee('Nuevo proveedor')
            ->assertSee('Nombre o razón social');
    }

    public function test_el_acopiador_no_entra_a_las_compras_de_la_planta(): void
    {
        $acopiador = User::where('role', 'acopiador')->firstOrFail();

        $this->actingAs($acopiador)
            ->get(route('produccion.compras.index'))
            ->assertRedirect(route('dashboard'));
    }

    /**
     * @param  array<int, array{supply_id: int, quantity: float, unit_cost: float}>  $items
     * @return array<string, mixed>
     */
    private function datosCompra(array $items): array
    {
        return [
            'new_supplier_name' => $this->proveedor->name,
            'new_supplier_document' => $this->proveedor->document,
            'purchase_date' => now()->format('Y-m-d'),
            'document_number' => 'F001-00123',
            'notes' => null,
            'items' => $items,
        ];
    }
}
