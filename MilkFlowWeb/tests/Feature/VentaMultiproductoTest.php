<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\ClientType;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\ProducerDeduction;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SystemPrice;
use App\Models\User;
use App\Services\Ventas\VentaService;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La caja vende cualquier producto del catálogo, no solo moldes de queso.
 *
 * El yogurt de la semilla vale S/ 7 / 8 / 9.50 contra los S/ 18 / 19 / 20 del
 * queso: si el precio saliera de una constante, estas pruebas no pasarían.
 */
class VentaMultiproductoTest extends TestCase
{
    use RefreshDatabase;

    private User $vendedor;

    private Product $queso;

    private Product $yogurt;

    private VentaService $ventas;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->vendedor = User::where('role', 'personal_venta')->firstOrFail();
        $this->ventas = app(VentaService::class);
        $this->queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
        $this->yogurt = Product::where('item_code', 'YOGURT_FRESA_1L')->firstOrFail();

        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 20);
        InventoryStock::adjustStock('YOGURT_FRESA_1L', 30);
    }

    public function test_una_venta_lleva_varios_productos_y_descuenta_el_stock_de_cada_uno(): void
    {
        $quesoAntes = InventoryStock::getStock('CHEESE_MOLD_UNITS');
        $yogurtAntes = InventoryStock::getStock('YOGURT_FRESA_1L');

        $venta = $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $this->clienteLocal()->id,
            'items' => [
                ['product_id' => $this->queso->id, 'quantity' => 2],
                ['product_id' => $this->yogurt->id, 'quantity' => 4],
            ],
        ]);

        // 2 × 20.00 (queso local) + 4 × 9.50 (yogurt local) = 78.00
        $this->assertEquals(78.00, (float) $venta->total_amount);
        $this->assertCount(2, $venta->items);
        $this->assertEquals(6, (int) $venta->cheese_molds_quantity);

        $this->assertEquals($quesoAntes - 2, InventoryStock::getStock('CHEESE_MOLD_UNITS'));
        $this->assertEquals($yogurtAntes - 4, InventoryStock::getStock('YOGURT_FRESA_1L'));
    }

    public function test_cada_producto_cobra_su_propia_tarifa_segun_el_cliente(): void
    {
        $proveedor = Customer::where('type', 'proveedor')->firstOrFail();

        $venta = $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $proveedor->id,
            'items' => [
                ['product_id' => $this->queso->id, 'quantity' => 1],
                ['product_id' => $this->yogurt->id, 'quantity' => 1],
            ],
        ]);

        $porProducto = $venta->items->keyBy('product_id');

        $this->assertEquals(18.00, (float) $porProducto[$this->queso->id]->unit_price);
        $this->assertEquals(7.00, (float) $porProducto[$this->yogurt->id]->unit_price);
        $this->assertEquals(25.00, (float) $venta->total_amount);
    }

    public function test_la_escalera_de_mayorista_se_mira_por_renglon(): void
    {
        $venta = $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $this->clienteLocal()->id,
            'items' => [
                // 10 yogures son compra grande de yogurt: tarifa mayorista.
                ['product_id' => $this->yogurt->id, 'quantity' => 10],
                // 2 quesos no: siguen a tarifa local.
                ['product_id' => $this->queso->id, 'quantity' => 2],
            ],
        ]);

        $porProducto = $venta->items->keyBy('product_id');

        $this->assertEquals(8.00, (float) $porProducto[$this->yogurt->id]->unit_price);
        $this->assertEquals(20.00, (float) $porProducto[$this->queso->id]->unit_price);
        $this->assertEquals(120.00, (float) $venta->total_amount);
    }

    public function test_el_precio_unitario_de_la_venta_nunca_queda_nulo(): void
    {
        $venta = $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $this->clienteLocal()->id,
            'items' => [
                ['product_id' => $this->queso->id, 'quantity' => 2],
                ['product_id' => $this->yogurt->id, 'quantity' => 4],
            ],
        ]);

        // El móvil ya instalado lo lee como Double no nulo: 78.00 / 6 = 13.00.
        $this->assertNotNull($venta->unit_price);
        $this->assertEquals(13.00, (float) $venta->unit_price);
    }

    public function test_no_se_despacha_mas_de_lo_que_hay_de_un_producto(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('Stock insuficiente de Yogurt de fresa 1 L');

        $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $this->clienteLocal()->id,
            'items' => [
                ['product_id' => $this->yogurt->id, 'quantity' => 999],
            ],
        ]);
    }

    public function test_dos_renglones_del_mismo_producto_suman_para_el_control_de_stock(): void
    {
        $disponible = InventoryStock::getStock('YOGURT_FRESA_1L');

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('Stock insuficiente de Yogurt de fresa 1 L');

        $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $this->clienteLocal()->id,
            'items' => [
                ['product_id' => $this->yogurt->id, 'quantity' => $disponible],
                ['product_id' => $this->yogurt->id, 'quantity' => 1],
            ],
        ]);
    }

    public function test_el_formato_viejo_del_movil_sigue_registrando_una_venta_de_queso(): void
    {
        $venta = $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $this->clienteLocal()->id,
            'cheese_molds_quantity' => 3,
        ]);

        $this->assertCount(1, $venta->items);
        $this->assertSame($this->queso->id, $venta->items->first()->product_id);
        $this->assertEquals(60.00, (float) $venta->total_amount);
    }

    public function test_la_deduccion_a_cuenta_de_leche_dice_que_se_llevo_el_proveedor(): void
    {
        $productor = User::where('role', 'productor')->firstOrFail();
        $cliente = Customer::where('linked_user_id', $productor->id)->firstOrFail();

        $venta = $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $cliente->id,
            'payment_method' => 'descuento_leche',
            'items' => [
                ['product_id' => $this->queso->id, 'quantity' => 1],
                ['product_id' => $this->yogurt->id, 'quantity' => 2],
            ],
        ]);

        $deduccion = ProducerDeduction::where('sale_id', $venta->id)->firstOrFail();

        $this->assertStringContainsString('Moldes de Queso', $deduccion->concept);
        $this->assertStringContainsString('Yogurt de fresa', $deduccion->concept);
        $this->assertEquals((float) $venta->total_amount, (float) $deduccion->amount);
    }

    public function test_cambiar_la_tarifa_del_queso_en_su_producto_llega_hasta_el_mostrador(): void
    {
        $jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();

        $this->actingAs($jefePlanta)->post(route('produccion.productos.precios', $this->queso), [
            'tarifas' => $this->tarifasPorTipo(21.00, 22.00, 23.00),
            'process_hours' => 0,
            'is_active' => 1,
        ])->assertSessionHas('success');

        $this->assertEquals(23.00, (float) $this->queso->fresh()->price_local);

        $venta = $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $this->clienteLocal()->id,
            'items' => [['product_id' => $this->queso->id, 'quantity' => 1]],
        ]);

        $this->assertEquals(23.00, (float) $venta->total_amount);
    }

    public function test_la_tarifa_vigente_del_queso_queda_historizada_en_precios(): void
    {
        $jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($jefePlanta)->post(route('produccion.productos.precios', $this->queso), [
            'tarifas' => $this->tarifasPorTipo(25.00, 26.00, 27.00),
            'process_hours' => 0,
            'is_active' => 1,
        ])->assertSessionHas('success');

        // Al cambiar las tarifas de leche, la fila nueva copia lo que rige hoy
        // para el queso: el histórico sigue diciendo lo que se cobraba.
        $this->actingAs($admin)->post('/admin/precios', [
            'season_name' => 'Temporada de prueba',
            'price_milk_base' => '1.50',
            'price_milk_water_penalty_low' => '1.20',
            'price_milk_water_penalty_high' => '0.90',
        ])->assertSessionHas('success');

        $this->assertEquals(25.00, (float) SystemPrice::current()->price_cheese_provider);
    }

    public function test_la_caja_y_el_recibo_muestran_el_detalle_de_lo_vendido(): void
    {
        $venta = $this->ventas->registrarVenta($this->vendedor, [
            'customer_id' => $this->clienteLocal()->id,
            'items' => [
                ['product_id' => $this->queso->id, 'quantity' => 1],
                ['product_id' => $this->yogurt->id, 'quantity' => 2],
            ],
        ]);

        $this->actingAs($this->vendedor)
            ->get(route('ventas.receipt', $venta))
            ->assertOk()
            ->assertSee('Moldes de Queso Madurado Huata')
            ->assertSee('Yogurt de fresa 1 L');

        $this->actingAs($this->vendedor)
            ->get(route('ventas.create'))
            ->assertOk()
            ->assertSee('Qué se despacha')
            ->assertSee('Yogurt de fresa 1 L');
    }

    public function test_la_caja_web_registra_una_venta_de_dos_productos(): void
    {
        $this->actingAs($this->vendedor)
            ->post(route('ventas.store'), [
                'customer_id' => $this->clienteLocal()->id,
                'items' => [
                    ['product_id' => $this->queso->id, 'quantity' => 1],
                    ['product_id' => $this->yogurt->id, 'quantity' => 3],
                ],
            ])
            ->assertSessionHasNoErrors();

        $venta = Sale::latest('id')->firstOrFail();

        $this->assertCount(2, $venta->items);
        $this->assertEquals(48.50, (float) $venta->total_amount);
    }

    private function clienteLocal(): Customer
    {
        return Customer::where('type', 'local')->firstOrFail();
    }

    /**
     * Las tarifas con el formato que espera la pantalla: una por tipo.
     *
     * @return array<int, float>
     */
    private function tarifasPorTipo(float $proveedor, float $mayorista, float $local): array
    {
        return [
            ['client_type_id' => ClientType::where('slug', 'proveedor')->value('id'), 'price_per_unit' => $proveedor],
            ['client_type_id' => ClientType::where('slug', 'mayorista')->value('id'), 'price_per_unit' => $mayorista],
            ['client_type_id' => ClientType::where('slug', 'local')->value('id'), 'price_per_unit' => $local],
        ];
    }
}
