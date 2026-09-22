<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\ClientType;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\User;
use App\Services\Produccion\CatalogoService;
use App\Services\Sistema\SistemaService;
use App\Services\Ventas\VentaService;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * El precio siempre sale del producto.
 *
 * El tipo de cliente solo dice a quién agrupa, desde qué cantidad aplica y a
 * qué rol del padrón se reconoce solo. No tiene tarifa propia: si la tuviera,
 * una mantequilla nueva heredaría el precio del queso.
 */
class TipoClienteTarifaTest extends TestCase
{
    use RefreshDatabase;

    private Product $queso;

    private Product $yogurt;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
        $this->yogurt = Product::where('item_code', 'YOGURT_FRESA_1L')->firstOrFail();
    }

    public function test_el_tipo_de_cliente_no_guarda_ningun_precio(): void
    {
        // Si lo guardara, un producto nuevo heredaría el precio del queso.
        $this->assertFalse(
            Schema::hasColumn('client_types', 'price_per_unit'),
            'El tipo de cliente no debe tener tarifa propia.'
        );
    }

    public function test_los_tres_tipos_de_siempre_quedan_sembrados_con_sus_tarifas(): void
    {
        $this->assertSame(3, ClientType::count());

        $proveedor = ClientType::where('slug', 'proveedor')->firstOrFail();
        $mayorista = ClientType::where('slug', 'mayorista')->firstOrFail();

        $this->assertSame(['productor'], $proveedor->rolesReconocidos());
        $this->assertEquals(0, (float) $proveedor->min_quantity);
        $this->assertEquals(10, (float) $mayorista->min_quantity);

        // Cada producto conservó lo que ya cobraba a cada tipo.
        $this->assertEquals(18.00, $proveedor->priceFor($this->queso));
        $this->assertEquals(7.00, $proveedor->priceFor($this->yogurt));
    }

    public function test_un_tipo_sin_tarifa_para_ese_producto_cobra_la_del_publico(): void
    {
        $empleados = app(SistemaService::class)->crearTipoCliente([
            'name' => 'Empleado',
            'auto_roles' => ['personal_venta'],
        ]);

        // Nadie le cargó tarifa: paga lo que paga el público por ese producto.
        $this->assertFalse($empleados->tieneTarifaPropia($this->queso));
        $this->assertEquals(20.00, $empleados->priceFor($this->queso));
        $this->assertEquals(9.50, $empleados->priceFor($this->yogurt));
    }

    public function test_la_tarifa_del_producto_manda_sobre_la_del_publico(): void
    {
        $empleados = app(SistemaService::class)->crearTipoCliente(['name' => 'Empleado']);

        app(CatalogoService::class)->fijarTarifas($this->queso, [$empleados->id => 12.00]);

        $empleados = $empleados->fresh();

        $this->assertEquals(12.00, $empleados->priceFor($this->queso));
        // El yogurt no se tocó: sigue pagando la de público.
        $this->assertEquals(9.50, $empleados->priceFor($this->yogurt));
    }

    public function test_quitar_la_tarifa_devuelve_ese_tipo_a_la_del_publico(): void
    {
        $empleados = app(SistemaService::class)->crearTipoCliente(['name' => 'Empleado']);

        $catalogo = app(CatalogoService::class);
        $catalogo->fijarTarifas($this->queso, [$empleados->id => 12.00]);
        $catalogo->fijarTarifas($this->queso, [$empleados->id => '']);

        $this->assertFalse($empleados->fresh()->tieneTarifaPropia($this->queso));
        $this->assertEquals(20.00, $empleados->fresh()->priceFor($this->queso));
    }

    public function test_un_producto_sin_ninguna_tarifa_no_se_puede_vender(): void
    {
        $vendedor = User::where('role', 'personal_venta')->firstOrFail();
        $this->yogurt->clientTypePrices()->delete();
        InventoryStock::adjustStock('YOGURT_FRESA_1L', 10);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('no tiene tarifa cargada para este cliente');

        app(VentaService::class)->registrarVenta($vendedor, [
            'customer_id' => Customer::where('type', 'local')->firstOrFail()->id,
            'items' => [['product_id' => $this->yogurt->id, 'quantity' => 1]],
        ]);
    }

    public function test_al_productor_del_padron_se_le_reconoce_su_tarifa_sin_importar_la_cantidad(): void
    {
        $productor = User::where('role', 'productor')->firstOrFail();
        $cliente = Customer::where('linked_user_id', $productor->id)->firstOrFail();

        // Aunque se lleve 50, sigue siendo proveedor: no le cae la mayorista.
        $this->assertEquals(18.00, $this->queso->priceForCustomer($cliente, 1));
        $this->assertEquals(18.00, $this->queso->priceForCustomer($cliente, 50));
    }

    public function test_un_tipo_nuevo_atado_a_un_rol_se_aplica_solo(): void
    {
        $empleados = app(SistemaService::class)->crearTipoCliente([
            'name' => 'Empleado',
            'auto_roles' => ['personal_venta'],
        ]);

        app(CatalogoService::class)->fijarTarifas($this->queso, [$empleados->id => 5.00]);

        $vendedor = User::where('role', 'personal_venta')->firstOrFail();

        $cliente = Customer::create([
            'first_name' => 'Rosa',
            'last_name' => 'Quispe',
            'type' => 'local',
            'linked_user_id' => $vendedor->id,
        ]);

        // No hubo que asignarle nada: su rol le da la tarifa.
        $this->assertEquals(5.00, $this->queso->fresh()->priceForCustomer($cliente, 1));
    }

    public function test_el_tramo_por_cantidad_mejora_la_tarifa_pero_nunca_la_empeora(): void
    {
        $local = Customer::where('type', 'local')->firstOrFail();
        $mayorista = Customer::where('type', 'mayorista')->firstOrFail();

        // Un local que se lleva 12 alcanza el tramo mayorista.
        $this->assertEquals(20.00, $this->queso->priceForCustomer($local, 1));
        $this->assertEquals(19.00, $this->queso->priceForCustomer($local, 12));

        // Un mayorista que se lleva 1 conserva la suya.
        $this->assertEquals(19.00, $this->queso->priceForCustomer($mayorista, 1));
    }

    public function test_dos_tipos_no_pueden_reclamar_el_mismo_rol(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('un rol solo puede dar una tarifa');

        app(SistemaService::class)->crearTipoCliente([
            'name' => 'Otro para productores',
            'auto_roles' => ['productor'],
        ]);
    }

    public function test_no_se_borra_un_tipo_con_clientes_asignados(): void
    {
        $local = ClientType::where('slug', 'local')->firstOrFail();
        Customer::where('type', 'local')->firstOrFail()->update(['client_type_id' => $local->id]);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('cliente(s) asignados');

        app(SistemaService::class)->eliminarTipoCliente($local->fresh());
    }

    public function test_el_admin_administra_los_tipos_desde_la_web(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.tipos-cliente.index'))
            ->assertOk()
            ->assertSee('Tipos de Cliente')
            ->assertSee('Mayorista')
            // El precio no se administra aquí.
            ->assertDontSee('name="price_per_unit"', false);

        $this->actingAs($admin)
            ->post(route('admin.tipos-cliente.store'), [
                'name' => 'Convenio colegio',
                'min_quantity' => 20,
                'description' => 'Pedido mensual del colegio.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('client_types', ['slug' => 'convenio-colegio', 'min_quantity' => 20]);
    }

    public function test_el_jefe_de_planta_pone_la_tarifa_por_tipo_desde_productos(): void
    {
        $jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();
        $mayorista = ClientType::where('slug', 'mayorista')->firstOrFail();
        $proveedor = ClientType::where('slug', 'proveedor')->firstOrFail();
        $local = ClientType::where('slug', 'local')->firstOrFail();

        $this->actingAs($jefePlanta)
            ->post(route('produccion.productos.precios', $this->yogurt), [
                'tarifas' => [
                    ['client_type_id' => $proveedor->id, 'price_per_unit' => 7.00],
                    ['client_type_id' => $mayorista->id, 'price_per_unit' => 7.25],
                    ['client_type_id' => $local->id, 'price_per_unit' => 9.50],
                ],
                'process_hours' => 6,
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertEquals(7.25, $mayorista->fresh()->priceFor($this->yogurt));
        // Y la columna heredada, que es la que lee el móvil, quedó alineada.
        $this->assertEquals(7.25, (float) $this->yogurt->fresh()->price_wholesale);
    }

    public function test_quitar_el_renglon_devuelve_ese_tipo_a_la_tarifa_del_publico(): void
    {
        $jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();
        $mayorista = ClientType::where('slug', 'mayorista')->firstOrFail();
        $local = ClientType::where('slug', 'local')->firstOrFail();

        $this->assertTrue($mayorista->tieneTarifaPropia($this->yogurt));

        // Se guarda dejando solo el renglón de local: el formulario es la verdad.
        $this->actingAs($jefePlanta)
            ->post(route('produccion.productos.precios', $this->yogurt), [
                'tarifas' => [
                    ['client_type_id' => $local->id, 'price_per_unit' => 9.50],
                ],
                'process_hours' => 6,
                'is_active' => 1,
            ])
            ->assertSessionHasNoErrors();

        $this->assertFalse($mayorista->fresh()->tieneTarifaPropia($this->yogurt));
        $this->assertEquals(9.50, $mayorista->fresh()->priceFor($this->yogurt->fresh()));
    }

    public function test_la_caja_recibe_los_tipos_y_la_tarifa_de_cada_producto(): void
    {
        $vendedor = User::where('role', 'personal_venta')->firstOrFail();

        app(SistemaService::class)->crearTipoCliente([
            'name' => 'Convenio colegio',
            'min_quantity' => 20,
        ]);

        $this->actingAs($vendedor)
            ->get(route('ventas.create'))
            ->assertOk()
            ->assertViewHas('tiposCliente', fn ($tipos) => $tipos->contains('slug', 'convenio-colegio'))
            ->assertSee('Convenio colegio');
    }

    public function test_el_buscador_de_clientes_dice_el_rol_del_padron(): void
    {
        $vendedor = User::where('role', 'personal_venta')->firstOrFail();
        $productor = User::where('role', 'productor')->firstOrFail();
        $cliente = Customer::where('linked_user_id', $productor->id)->firstOrFail();

        $this->actingAs($vendedor)
            ->getJson(route('ventas.customers.search', ['q' => $cliente->last_name]))
            ->assertOk()
            ->assertJsonFragment(['linked_role' => 'productor']);
    }

    public function test_un_tipo_puede_reconocer_varios_roles_de_una_vez(): void
    {
        $empleados = app(SistemaService::class)->crearTipoCliente([
            'name' => 'Empleado',
            'auto_roles' => ['personal_venta', 'acopiador', 'inspector_calidad'],
        ]);

        app(CatalogoService::class)->fijarTarifas($this->queso, [$empleados->id => 5.00]);

        $this->assertCount(3, $empleados->fresh()->rolesReconocidos());

        // A cualquiera de esos tres puestos se le reconoce la misma tarifa.
        foreach (['personal_venta', 'acopiador', 'inspector_calidad'] as $rol) {
            $trabajador = User::where('role', $rol)->firstOrFail();

            $cliente = Customer::create([
                'first_name' => 'Empleado',
                'last_name' => ucfirst($rol),
                'type' => 'local',
                'linked_user_id' => $trabajador->id,
            ]);

            $this->assertEquals(
                5.00,
                $this->queso->fresh()->priceForCustomer($cliente, 1),
                "El rol {$rol} debería recibir la tarifa de empleado."
            );
        }
    }

    public function test_al_editar_un_tipo_se_reemplazan_sus_roles(): void
    {
        $sistema = app(SistemaService::class);

        $empleados = $sistema->crearTipoCliente([
            'name' => 'Empleado',
            'auto_roles' => ['personal_venta', 'acopiador'],
        ]);

        $sistema->actualizarTipoCliente($empleados, [
            'name' => 'Empleado',
            'auto_roles' => ['acopiador'],
            'is_active' => true,
        ]);

        $this->assertSame(['acopiador'], $empleados->fresh()->rolesReconocidos());

        // El rol liberado queda disponible para otro tipo.
        $otro = $sistema->crearTipoCliente([
            'name' => 'Solo vendedores',
            'auto_roles' => ['personal_venta'],
        ]);

        $this->assertSame(['personal_venta'], $otro->rolesReconocidos());
    }

    public function test_el_cliente_que_vuelve_se_reconoce_por_su_nombre_y_conserva_su_tarifa(): void
    {
        $vendedor = User::where('role', 'personal_venta')->firstOrFail();
        $mayorista = Customer::where('type', 'mayorista')->firstOrFail();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 20);
        $clientesAntes = Customer::count();

        // Se teclea su nombre como si fuera cliente nuevo, sin elegirlo de la lista.
        $venta = app(VentaService::class)->registrarVenta($vendedor, [
            'new_first_name' => mb_strtoupper($mayorista->first_name),
            'new_last_name' => '  '.mb_strtolower($mayorista->last_name).' ',
            'items' => [['product_id' => $this->queso->id, 'quantity' => 1]],
        ]);

        // Ni duplicado ni tarifa de local: es el mismo cliente de siempre.
        $this->assertSame($clientesAntes, Customer::count());
        $this->assertSame($mayorista->id, $venta->customer_id);
        $this->assertEquals(19.00, (float) $venta->items->first()->unit_price);
    }

    public function test_un_nombre_desconocido_si_crea_cliente_nuevo(): void
    {
        $vendedor = User::where('role', 'personal_venta')->firstOrFail();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 20);
        $clientesAntes = Customer::count();

        $venta = app(VentaService::class)->registrarVenta($vendedor, [
            'new_first_name' => 'Aurelia',
            'new_last_name' => 'Ccopa Mamani',
            'items' => [['product_id' => $this->queso->id, 'quantity' => 1]],
        ]);

        $this->assertSame($clientesAntes + 1, Customer::count());
        // Cliente nuevo que se lleva uno: tarifa local.
        $this->assertEquals(20.00, (float) $venta->items->first()->unit_price);
    }

    public function test_el_cliente_nuevo_que_se_lleva_mucho_entra_como_mayorista(): void
    {
        $vendedor = User::where('role', 'personal_venta')->firstOrFail();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 40);

        $venta = app(VentaService::class)->registrarVenta($vendedor, [
            'new_first_name' => 'Bodega',
            'new_last_name' => 'Santa Rosa',
            'items' => [['product_id' => $this->queso->id, 'quantity' => 15]],
        ]);

        $this->assertEquals(19.00, (float) $venta->items->first()->unit_price);
    }

    public function test_el_mostrador_reconoce_al_cliente_desde_el_navegador(): void
    {
        $vendedor = User::where('role', 'personal_venta')->firstOrFail();
        $cliente = Customer::where('type', 'mayorista')->firstOrFail();

        $this->actingAs($vendedor)
            ->getJson(route('ventas.customers.match', [
                'last_name' => mb_strtoupper($cliente->last_name),
                'first_name' => $cliente->first_name,
            ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $cliente->id]);

        // Un nombre que no existe no devuelve nada.
        $this->actingAs($vendedor)
            ->getJson(route('ventas.customers.match', ['last_name' => 'Nadie De Por Aqui']))
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_una_compra_fraccionada_nunca_cae_en_la_tarifa_de_proveedor(): void
    {
        // Con el tramo más bajo en 1, media unidad no alcanza ningún tramo.
        ClientType::whereIn('slug', ['proveedor', 'local'])->update(['min_quantity' => 1]);

        // Sin cliente todavía elegido, que es lo que ve la caja al abrir.
        $sinCliente = ClientType::paraCliente(null, 0.5);

        $this->assertNotNull($sinCliente);
        $this->assertFalse($sinCliente->reconoceAlgunRol(), 'Un desconocido no puede cobrar tarifa de proveedor.');
        $this->assertSame('local', $sinCliente->slug);

        // Y con un cliente cuyo tipo ya no corresponde a ninguno vigente.
        $huerfano = Customer::create([
            'first_name' => 'Quien',
            'last_name' => 'Sea',
            'type' => 'categoria_que_ya_no_existe',
        ]);

        $this->assertEquals(20.00, $this->queso->priceForCustomer($huerfano, 0.5));
    }
}
