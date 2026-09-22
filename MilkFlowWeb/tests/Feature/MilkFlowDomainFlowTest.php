<?php

namespace Tests\Feature;

use App\Models\ClientType;
use App\Models\CollectionPriceRule;
use App\Models\CollectionRecord;
use App\Models\CollectionRoute;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\LactoscanAnalysis;
use App\Models\PlantReception;
use App\Models\ProducerDeduction;
use App\Models\ProducerSettlement;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SystemPrice;
use App\Models\TechnicalVisit;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneChangeRequest;
use App\Services\Acopio\JornadaOperativa;
use Carbon\Carbon;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MilkFlowDomainFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);
    }

    public function test_login_and_dashboard_access()
    {
        $admin = User::where('role', 'admin')->first();
        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee($admin->name);
    }

    public function test_flowmeter_verification_passes_milk_to_stock()
    {
        $jefe = User::where('role', 'jefe_produccion')->first();
        $acopiador = User::where('role', 'acopiador')->first();
        $zona = Zone::first();

        $route = CollectionRoute::firstOrCreate(
            ['date' => app(JornadaOperativa::class)->fecha(), 'zone_id' => $zona->id],
            ['collector_id' => $acopiador->id, 'start_time' => '04:30:00', 'status' => 'descargada_planta', 'total_collected_liters' => 148.0]
        );

        $initialStock = InventoryStock::getStock('MILK_RAW_LITERS');

        $response = $this->actingAs($jefe)->post("/planta/verificar/{$route->id}", [
            'flowmeter_liters' => 150.0,
            'verification_status' => 'verificado',
            'observation' => 'Test caudalímetro conforme',
        ]);

        $response->assertSessionHas('success');
        $newStock = InventoryStock::getStock('MILK_RAW_LITERS');
        $this->assertEquals($initialStock + 150.0, $newStock);
    }

    public function test_sales_rates_differentiated_and_receipt_generation()
    {
        $seller = User::where('role', 'personal_venta')->first();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 20);

        // 1. Venta a Proveedor -> S/ 18
        $proveedorCust = Customer::where('type', 'proveedor')->first();
        $response = $this->actingAs($seller)->post('/ventas', [
            'customer_id' => $proveedorCust->id,
            'items' => $this->pedidoDeQueso(2),
        ]);
        $response->assertRedirect();
        $sale = Sale::latest()->first();
        $this->assertEquals(18.00, (float) $sale->unit_price);
        $this->assertEquals(36.00, (float) $sale->total_amount);
        $this->assertEquals('efectivo', $sale->payment_method);

        // 2. Venta a Cliente local con >= 10 moldes -> tarifa mayorista S/ 19
        $localCust = Customer::where('type', 'local')->first();
        $salesCountBefore = Sale::count();
        $response2 = $this->actingAs($seller)->post('/ventas', [
            'customer_id' => $localCust->id,
            'items' => $this->pedidoDeQueso(10),
        ]);
        $response2->assertRedirect();
        $wholesaleSale = Sale::orderBy('id', 'desc')->first();
        $this->assertEquals(19.00, (float) $wholesaleSale->unit_price);
        $this->assertEquals(190.00, (float) $wholesaleSale->total_amount);
    }

    public function test_mobile_login_and_route_sync_api()
    {
        $acopiador = User::where('role', 'acopiador')->first();
        $zona = Zone::first();

        // Asegurar ruta asignada hoy
        CollectionRoute::firstOrCreate(
            ['date' => app(JornadaOperativa::class)->fecha(), 'collector_id' => $acopiador->id],
            ['zone_id' => $zona->id, 'start_time' => '04:30:00', 'status' => 'asignada']
        );

        // 1. Login API
        $response = $this->postJson('/api/mobile/login', [
            'email' => $acopiador->email,
            'password' => 'password',
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure(['token', 'user', 'announcements']);

        $token = $response->json('token');

        // 2. Descargar ruta para trabajo offline
        $routeResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/mobile/collector/route');
        $routeResponse->assertStatus(200);
        $routeResponse->assertJsonStructure(['route_id', 'date', 'zone', 'producers']);
    }

    public function test_producer_acopio_view_shows_today_liters_and_weekly_accumulator()
    {
        $producer = User::where('role', 'productor')->first();
        $response = $this->actingAs($producer)->get('/productor/acopio');
        $response->assertStatus(200);
        $response->assertSee('Entrega de Hoy');
        $response->assertSee('Acumulado Semana Activa');
        $response->assertSee('Pago Sumatorio Semanal');
        $response->assertSee('Por Día');
        $response->assertSee('Por Semana');
        $response->assertSee('Por Mes');
    }

    public function test_producer_modules_access_and_zone_change_request()
    {
        $producer = User::where('role', 'productor')->first();
        $targetZone = Zone::where('id', '!=', $producer->zone_id)->first();

        // 1. Visitar módulos de productor
        $this->actingAs($producer)->get('/productor/zonas')->assertStatus(200);
        $this->actingAs($producer)->get('/productor/descuentos')->assertStatus(200);
        $this->actingAs($producer)->get('/productor/pagos')->assertStatus(200);
        $this->actingAs($producer)->get('/productor/calidad')->assertStatus(200);

        // 2. Solicitar cambio de zona
        $response = $this->actingAs($producer)->post('/productor/zonas/solicitar', [
            'requested_zone_id' => $targetZone->id,
            'reason' => 'Test rotación de pastoreo hacia sector alterno',
        ]);
        $response->assertRedirect('/productor/zonas');
        $this->assertDatabaseHas('zone_change_requests', [
            'producer_id' => $producer->id,
            'requested_zone_id' => $targetZone->id,
            'status' => 'pendiente',
        ]);
    }

    public function test_closing_and_paying_weekly_settlement_resets_weekly_accumulator()
    {
        $producer = User::where('role', 'productor')->first();
        $admin = User::where('role', 'admin')->first();

        // Ejecutar liquidación semanal
        $response = $this->actingAs($admin)->post("/productor/liquidar/{$producer->id}");
        $response->assertSessionHas('success');

        // Verificar que la liquidación se registró como pagada
        $this->assertDatabaseHas('producer_settlements', [
            'producer_id' => $producer->id,
            'status' => 'pagado',
        ]);

        // Verificar que deducciones pendientes se marcaron como descontadas
        $this->assertDatabaseMissing('producer_deductions', [
            'producer_id' => $producer->id,
            'status' => 'pendiente',
        ]);
    }

    public function test_admin_can_view_and_approve_zone_change_request_from_dashboard_and_solicitudes_panel()
    {
        $admin = User::where('role', 'admin')->first();
        $producer = User::where('role', 'productor')->first();
        $newZone = Zone::where('id', '!=', $producer->zone_id)->first();

        // Crear solicitud pendiente
        $req = ZoneChangeRequest::create([
            'producer_id' => $producer->id,
            'current_zone_id' => $producer->zone_id,
            'requested_zone_id' => $newZone->id,
            'reason' => 'Traslado de pasturas hacia nuevo sector',
            'status' => 'pendiente',
        ]);

        // 1. Dashboard de Admin muestra la solicitud pendiente y el botón de decisión
        $dashResponse = $this->actingAs($admin)->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee('Solicitudes de Rotación de Zona Pendientes');
        $dashResponse->assertSee($producer->name);

        // 2. Panel dedicado de Solicitudes de Zona
        $solResponse = $this->actingAs($admin)->get('/zonas/solicitudes');
        $solResponse->assertStatus(200);
        $solResponse->assertSee('Solicitudes de cambio de zona');
        $solResponse->assertSee($producer->name);

        // 3. Admin aprueba la solicitud
        $reviewResponse = $this->actingAs($admin)->post("/zonas/solicitud/{$req->id}/revisar", [
            'decision' => 'aprobado',
        ]);
        $reviewResponse->assertSessionHas('success');

        // 4. Verificar que se actualizó el estado de la solicitud y la zona del productor
        $this->assertEquals('aprobado', $req->fresh()->status);
        $this->assertEquals($newZone->id, $producer->fresh()->zone_id);
    }

    public function test_seasonal_pricing_update_and_cheese_sales_calculation()
    {
        $admin = User::where('role', 'admin')->first();

        // 1. El queso se tarifa en su producto del catálogo
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
        $jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();

        $this->actingAs($jefePlanta)->post("/produccion/productos/{$queso->id}/precios", [
            'tarifas' => $this->tarifasPorTipo(17.50, 18.50, 21.00),
            'process_hours' => 0,
            'is_active' => 1,
        ])->assertSessionHas('success');

        // 2. La leche se tarifa en las reglas de acopio
        $base = CollectionPriceRule::whereNull('metric')->firstOrFail();

        $this->actingAs($admin)->put("/admin/precios/tarifas/{$base->id}", [
            'name' => 'Tarifa base',
            'price_per_unit' => 1.50,
            'is_active' => 1,
        ])->assertSessionHas('success');

        // 3. Y Precios solo cierra la temporada, copiando lo que rige hoy
        $response = $this->actingAs($admin)->post('/admin/precios', [
            'season_name' => 'Temporada Seca 2026',
            'notes' => 'Ajuste estacional por sequía',
        ]);
        $response->assertRedirect('/admin/precios');

        $current = SystemPrice::current();
        $this->assertEquals(1.50, (float) $current->price_milk_base);

        // 4. La fila histórica copia del producto la tarifa que regía ese día
        $this->assertEquals(17.50, (float) $current->price_cheese_provider);

        // 5. Y el mostrador le cobra eso al proveedor
        $proveedorCust = Customer::where('type', 'proveedor')->first();
        $this->assertEquals(17.50, $queso->fresh()->priceForCustomer($proveedorCust, 1));
    }

    public function test_milk_quality_water_penalty_and_admin_payment_authorization()
    {
        $admin = User::where('role', 'admin')->first();
        $producer = User::where('role', 'productor')->first();
        $inspector = User::where('role', 'inspector_calidad')->first();

        // Registrar análisis con 4% de agua (debe penalizar a S/ 1.20)
        LactoscanAnalysis::create([
            'producer_id' => $producer->id,
            'inspector_id' => $inspector->id,
            'analysis_date' => date('Y-m-d'),
            'fat_percentage' => 3.2,
            'snf_percentage' => 8.1,
            'density' => 1.025,
            'water_addition_percentage' => 4.00,
            'ph_or_acidity' => 17.0,
            'verdict' => 'adulterada',
        ]);

        $priceInfo = SystemPrice::getMilkPriceForProducer($producer->id, date('Y-m-d'), date('Y-m-d'));
        $this->assertEquals('leve_descuento', $priceInfo['penalty_type']);
        $this->assertEquals(1.20, $priceInfo['price']);

        // Visitar panel de autorización
        $authResponse = $this->actingAs($admin)->get('/admin/pagos/autorizacion');
        $authResponse->assertStatus(200);
        $authResponse->assertSee('Panel de Autorización de Pagos a Proveedores');
        $authResponse->assertSee('Entregas Diarias de Leche');
        $authResponse->assertSee('Penalidad Semanal');

        // Autorizar pago individual
        $payResponse = $this->actingAs($admin)->post("/admin/pagos/autorizar/{$producer->id}");
        $payResponse->assertSessionHas('success');

        // Verificar que la liquidación se generó con la tarifa de penalidad de 1.20 aplicada a toda la semana
        $settlement = ProducerSettlement::where('producer_id', $producer->id)->latest('id')->first();
        $this->assertEquals(1.20, (float) $settlement->price_per_liter);
        $this->assertContains($settlement->status, ['autorizado', 'pagado']);
        $this->assertGreaterThan(0, (float) $settlement->deductions_total);
    }

    public function test_cheese_purchase_deducted_from_producer_milk_settlement()
    {
        $seller = User::where('role', 'personal_venta')->first();
        $producer = User::where('role', 'productor')->first();
        $customer = Customer::where('linked_user_id', $producer->id)->first();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 20);

        // Vender 2 quesos al proveedor con modalidad 'descuento_leche'
        $response = $this->actingAs($seller)->post('/ventas', [
            'customer_id' => $customer->id,
            'items' => $this->pedidoDeQueso(2),
            'payment_method' => 'descuento_leche',
        ]);
        $response->assertRedirect();

        // Verificar que se creó automáticamente la deducción pendiente para el proveedor
        $this->assertDatabaseHas('producer_deductions', [
            'producer_id' => $producer->id,
            'status' => 'pendiente',
        ]);
    }

    public function test_collector_cannot_see_zonas_menu_and_flow_of_closing_route_to_plant_flowmeter()
    {
        $collector = User::where('role', 'acopiador')->first();
        $jefe = User::where('role', 'jefe_produccion')->first();
        $zone = Zone::first();

        // 1. Acopiador ingresa a su panel: NO debe ver 'Zonas (1 a 4)' en su navegación
        $response = $this->actingAs($collector)->get('/acopio');
        $response->assertStatus(200);
        $response->assertDontSee('Zonas (1 a 4)');

        // 2. Ruta activa en campo: debe ver el botón 'Cerrar Ruta'
        $route = CollectionRoute::firstOrCreate(
            ['date' => app(JornadaOperativa::class)->fecha(), 'collector_id' => $collector->id],
            ['zone_id' => $zone->id, 'start_time' => '04:30:00', 'status' => 'en_ruta', 'total_collected_liters' => 85.0]
        );
        $route->status = 'en_ruta';
        $route->save();

        $responseRoute = $this->actingAs($collector)->get('/acopio');
        $responseRoute->assertSee('Cerrar Ruta');

        // 3. Acopiador presiona 'Cerrar Ruta' al llegar a planta
        $closeResponse = $this->actingAs($collector)->post("/acopio/ruta/{$route->id}/descargar");
        $closeResponse->assertSessionHas('success');

        $route->refresh();
        $this->assertEquals('descargada_planta', $route->status);

        // 4. Panel de acopio ahora muestra 'Ruta Cerrada' y bloquea modificaciones
        $responseClosed = $this->actingAs($collector)->get('/acopio');
        $responseClosed->assertSee('Ruta Cerrada');

        // 5. Al Jefe de Producción le aparece la carga lista para verificar con caudalímetro
        $responseJefe = $this->actingAs($jefe)->get('/planta/verificacion');
        $responseJefe->assertStatus(200);
        $responseJefe->assertSee('Esperando Caudalímetro');
    }

    public function test_multiple_collectors_logging_in_does_not_trigger_duplicate_route_exception()
    {
        $collectors = User::where('role', 'acopiador')->get();
        $this->assertGreaterThanOrEqual(4, $collectors->count());

        // Cada acopiador accede a /acopio; ninguno debe arrojar error 500
        foreach ($collectors as $acopiador) {
            $response = $this->actingAs($acopiador)->get('/acopio');
            $response->assertStatus(200);
        }
    }

    public function test_collector_cannot_access_or_see_dashboard_menu()
    {
        $collector = User::where('role', 'acopiador')->first();

        // 1. Acceso a /dashboard redirige automáticamente a /acopio
        $responseDash = $this->actingAs($collector)->get('/dashboard');
        $responseDash->assertRedirect(route('acopio.index'));

        // 2. En /acopio, no debe figurar la opción "Dashboard" en la barra de navegación
        $responseAcopio = $this->actingAs($collector)->get('/acopio');
        $responseAcopio->assertStatus(200);
        $responseAcopio->assertDontSee('<span>Dashboard</span>', false);
    }

    public function test_acopio_search_modal_and_plant_history_with_loss_observations()
    {
        $collector = User::where('role', 'acopiador')->first();
        $jefe = User::where('role', 'jefe_produccion')->first();
        $zone = Zone::first();

        // 1. Crear una ruta histórica previa con recepción y merma en planta (400 L campo vs 395 L caudalímetro)
        $pastRoute = CollectionRoute::create([
            'date' => Carbon::parse(app(JornadaOperativa::class)->fecha())->subDay()->toDateString(),
            'zone_id' => $zone->id,
            'collector_id' => $collector->id,
            'start_time' => '04:30:00',
            'status' => 'verificada',
            'total_collected_liters' => 400.0,
        ]);

        PlantReception::create([
            'collection_route_id' => $pastRoute->id,
            'verifier_id' => $jefe->id,
            'collector_declared_liters' => 400.0,
            'flowmeter_liters' => 395.0,
            'difference_liters' => -5.0,
            'verification_status' => 'verificado',
            'observation' => 'Merma de 5L por espuma en descarga',
            'verified_at' => now(),
        ]);

        // 2. Cargar la vista de /acopio para el acopiador
        $response = $this->actingAs($collector)->get('/acopio');
        $response->assertStatus(200);

        // Verifica la existencia del buscador en tiempo real
        $response->assertSee('producerSearchInput');
        $response->assertSee('Buscar proveedor por nombre completo o número de DNI');

        // Verifica modales de registro de litros y detalle histórico (ojito)
        $response->assertSee('recordModal');
        $response->assertSee('historyModal');
        $response->assertSee('Registrar');

        // Verifica el historial de rutas con el cuadre de planta y observaciones
        $response->assertSee('Historial de Acopio por Rutas y Cuadre de Planta');
        $response->assertSee('400.00 L');
        $response->assertSee('395.00 L');
        $response->assertSee('-5.00 L (Merma)');
        $response->assertSee('Merma de 5L por espuma en descarga');

        // 3. Registrar entrega para un productor y verificar actualización y reordenamiento
        $activeRoute = CollectionRoute::where('collector_id', $collector->id)->where('date', app(JornadaOperativa::class)->fecha())->first();
        $firstProducer = $activeRoute->zone->producers->first();

        $deliveryResponse = $this->actingAs($collector)->post(route('acopio.delivery', $activeRoute->id), [
            'producer_id' => $firstProducer->id,
            'liters' => 32.5,
            'notes' => 'Porongo de aluminio',
        ]);
        $deliveryResponse->assertSessionHas('success');

        $responseAfter = $this->actingAs($collector)->get('/acopio');
        $responseAfter->assertStatus(200);
        $responseAfter->assertSee('32.50 L');
        $responseAfter->assertSee('Acopiado');
        $responseAfter->assertSee('Porongo de aluminio');
    }

    public function test_collector_sidebar_has_historial_and_reportes_and_accesses_module()
    {
        $collector = User::where('role', 'acopiador')->first();
        $jefe = User::where('role', 'jefe_produccion')->first();
        $zone = Zone::first();

        // 1. En /acopio, el sidebar debe mostrar el enlace "Historial y Reportes" justo bajo Acopio 4:30 AM
        $responseAcopio = $this->actingAs($collector)->get('/acopio');
        $responseAcopio->assertStatus(200);
        $responseAcopio->assertSee('Historial y Reportes');
        $responseAcopio->assertSee(route('acopio.historial'));

        // 2. Acceder al módulo /acopio/historial
        $pastRoute = CollectionRoute::updateOrCreate(
            ['date' => '2026-08-20', 'zone_id' => $zone->id],
            ['collector_id' => $collector->id, 'start_time' => '04:30:00', 'status' => 'verificada', 'total_collected_liters' => 250.0]
        );

        PlantReception::updateOrCreate(
            ['collection_route_id' => $pastRoute->id],
            [
                'verifier_id' => $jefe->id,
                'collector_declared_liters' => 250.0,
                'flowmeter_liters' => 248.5,
                'difference_liters' => -1.5,
                'verification_status' => 'verificado',
                'observation' => 'Caudalímetro conforme con leve espuma',
                'verified_at' => now(),
            ]
        );

        $responseHistorial = $this->actingAs($collector)->get('/acopio/historial');
        $responseHistorial->assertStatus(200);
        $responseHistorial->assertSee('Historial y Reportes de Acopio');
        $responseHistorial->assertSee('Rutas Realizadas');
        $responseHistorial->assertSee('Total Anotado (Campo)');
        $responseHistorial->assertSee('Caudalímetro (Planta)');
        $responseHistorial->assertSee('Balance Neto (Merma)');
        $responseHistorial->assertSee('248.50 L');
        $responseHistorial->assertSee('-1.50 L (Merma)');
        $responseHistorial->assertSee('Caudalímetro conforme con leve espuma');
        $responseHistorial->assertSee('Imprimir Reporte');
    }

    public function test_flowmeter_verification_strictly_enters_flowmeter_liters_and_allows_correction_with_delta_stock()
    {
        $jefe = User::where('role', 'jefe_produccion')->first();
        $collector = User::where('role', 'acopiador')->first();
        $zone = Zone::where('id', 2)->first() ?: Zone::first();

        $route = CollectionRoute::create([
            'date' => '2026-08-25',
            'zone_id' => $zone->id,
            'collector_id' => $collector->id,
            'start_time' => '04:30:00',
            'status' => 'descargada_planta',
            'total_collected_liters' => 157.0,
        ]);

        $initialStock = InventoryStock::getStock('MILK_RAW_LITERS');

        // 1. Jefe de producción verifica 150 L en caudalímetro (con estado incompleto por merma de 7 L)
        $response1 = $this->actingAs($jefe)->post("/planta/verificar/{$route->id}", [
            'flowmeter_liters' => 150.0,
            'verification_status' => 'incompleto',
            'observation' => 'Merma de 7L detectada en descarga',
        ]);

        $response1->assertSessionHas('success');
        $this->assertEquals($initialStock + 150.0, InventoryStock::getStock('MILK_RAW_LITERS'));

        $route->refresh();
        $this->assertEquals('150.00', $route->reception->flowmeter_liters);
        $this->assertEquals('-7.00', $route->reception->difference_liters);
        $this->assertEquals('incompleto', $route->reception->verification_status);

        // 2. Corrección posterior de medición: se ajusta a 152.0 L
        $response2 = $this->actingAs($jefe)->post("/planta/verificar/{$route->id}", [
            'flowmeter_liters' => 152.0,
            'verification_status' => 'incompleto',
            'observation' => 'Ajuste tras lectura final de caudalímetro: 152L',
        ]);

        $response2->assertSessionHas('success');
        // El stock debe aumentar en solo el delta (+2 L), totalizando initialStock + 152.0 L
        $this->assertEquals($initialStock + 152.0, InventoryStock::getStock('MILK_RAW_LITERS'));

        // 3. Verificar que el acopiador ve el estado "Incompleto" y no "Conforme"
        $responseAcopio = $this->actingAs($collector)->get('/acopio/historial');
        $responseAcopio->assertStatus(200);
        $responseAcopio->assertSee('Incompleto');
        $responseAcopio->assertSee('152.00 L');
        $responseAcopio->assertSee('-5.00 L (Merma)');
    }

    public function test_dashboard_is_removed_and_redirected_for_jefe_produccion()
    {
        $jefe = User::where('role', 'jefe_produccion')->first();

        // 1. Acceder a /dashboard debe redirigir a /planta/verificacion
        $response = $this->actingAs($jefe)->get('/dashboard');
        $response->assertRedirect(route('planta.verificacion'));

        // 2. En la vista de planta, el sidebar no debe mostrar el link 'Dashboard'
        $followResponse = $this->actingAs($jefe)->get(route('planta.verificacion'));
        $followResponse->assertStatus(200);
        $followResponse->assertDontSee('<span>Dashboard</span>', false);
        $followResponse->assertSee('Caudalímetro');
        $followResponse->assertDontSee('Quesería (-10L)');
    }

    public function test_producer_can_view_receipt_for_cheese_purchase_deduction()
    {
        $seller = User::where('role', 'personal_venta')->first();
        $producer = User::where('role', 'productor')->first();
        $otherProducer = User::where('role', 'productor')->where('id', '!=', $producer->id)->first();

        // 1. Crear cliente vinculado al proveedor
        $customer = Customer::firstOrCreate(
            ['linked_user_id' => $producer->id],
            [
                'first_name' => $producer->name,
                'last_name' => 'Proveedor',
                'dni_ruc' => $producer->dni ?: '40010001',
                'phone' => '952000001',
                'type' => 'proveedor',
                'is_wholesale_approved' => false,
            ]
        );

        // 2. Registrar venta con descuento en leche
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 10);
        $responseSale = $this->actingAs($seller)->post('/ventas', [
            'customer_id' => $customer->id,
            'items' => $this->pedidoDeQueso(3),
            'payment_method' => 'descuento_leche',
        ]);
        $responseSale->assertSessionHas('success');

        $sale = Sale::where('customer_id', $customer->id)->latest()->first();
        $this->assertNotNull($sale);

        $deduction = ProducerDeduction::where('producer_id', $producer->id)
            ->where('sale_id', $sale->id)
            ->first();
        $this->assertNotNull($deduction);
        $this->assertEquals($sale->id, $deduction->matched_sale->id);

        // 3. El proveedor consulta sus descuentos y ve el botón 'Ver Recibo'
        $responseDescuentos = $this->actingAs($producer)->get('/productor/descuentos');
        $responseDescuentos->assertStatus(200);
        $responseDescuentos->assertSee('Ver Recibo');
        $responseDescuentos->assertSee($sale->receipt_number);
        $responseDescuentos->assertSee('receiptModal');

        // 4. El proveedor consulta la vista formal de recibo
        $responseReceipt = $this->actingAs($producer)->get(route('ventas.receipt', $sale->id));
        $responseReceipt->assertStatus(200);
        $responseReceipt->assertSee($sale->receipt_number);
        $responseReceipt->assertSee('Volver a Descuentos');

        // 5. Seguridad: Otro proveedor no autorizado no puede consultar este recibo
        $responseUnauthorized = $this->actingAs($otherProducer)->get(route('ventas.receipt', $sale->id));
        $responseUnauthorized->assertStatus(403);
    }

    public function test_cash_closure_differentiates_physical_cash_from_milk_deductions_and_summarizes_customers()
    {
        $seller = User::where('role', 'personal_venta')->first();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 30);

        $provCustomer = Customer::where('type', 'proveedor')->first();
        $mayoristaCustomer = Customer::where('type', 'mayorista')->first();
        $localCustomer = Customer::where('type', 'local')->first();

        // 1. Venta a Proveedor: 2 moldes @ S/ 18 = S/ 36 (A CUENTA DE LECHE - no entra a caja)
        $this->actingAs($seller)->post('/ventas', [
            'customer_id' => $provCustomer->id,
            'items' => $this->pedidoDeQueso(2),
            'payment_method' => 'descuento_leche',
        ]);

        // 2. Venta a Mayorista: 10 moldes @ S/ 19 = S/ 190 (EFECTIVO EN CAJA)
        $this->actingAs($seller)->post('/ventas', [
            'customer_id' => $mayoristaCustomer->id,
            'items' => $this->pedidoDeQueso(10),
            'payment_method' => 'efectivo',
        ]);

        // 3. Venta a Local: 3 moldes @ S/ 20 = S/ 60 (EFECTIVO EN CAJA)
        $this->actingAs($seller)->post('/ventas', [
            'customer_id' => $localCustomer->id,
            'items' => $this->pedidoDeQueso(3),
            'payment_method' => 'efectivo',
        ]);

        // 4. Consultar /ventas y verificar Cierre de Caja y Arqueo
        $response = $this->actingAs($seller)->get('/ventas');
        $response->assertStatus(200);

        // Validar que se muestra el botón y cuadro de Arqueo y Cierre de Caja
        $response->assertSee('Cierre de Caja');
        $response->assertSee('toggleCierreCaja');
        $response->assertSee('cierreCajaPanel');
        $response->assertSee('Arqueo y Cierre de Caja');
        $response->assertSee('Efectivo Real en Caja');
        $response->assertSee('A Cuenta de Leche (Crédito)');

        // Validar totales exactos: Efectivo = 250.00, Descuento Leche = 36.00, Total = 286.00, Moldes = 15
        $response->assertSee('250.00');
        $response->assertSee('36.00');
        $response->assertSee('286.00');
        $response->assertSee('15');

        // El desglose se arma desde `client_types`, así que las filas llevan el
        // nombre del tipo y no las tres etiquetas fijas de antes.
        $response->assertSee('Proveedor de leche');
        $response->assertSee('Mayorista');
        $response->assertSee('Cliente local');
        $response->assertSee('Ventas de hoy');

        // 5. Confirmar y ejecutar el Cierre de Caja
        $closeResponse = $this->actingAs($seller)->post('/ventas/cierre-caja');
        $closeResponse->assertRedirect(route('ventas.index'));
        $closeResponse->assertSessionHas('success');

        // 6. Consultar /ventas tras el cierre: la bandeja de ventas de hoy debe volver a blanco
        $responseAfterClose = $this->actingAs($seller)->get('/ventas');
        $responseAfterClose->assertStatus(200);
        $responseAfterClose->assertSee('No hay ventas pendientes en el turno de hoy.');
        $responseAfterClose->assertSee('Las ventas ya están en el historial de recibos.');

        // 7. Consultar /ventas/recibos: deben figurar todos los recibos emitidos históricos
        $responseReceipts = $this->actingAs($seller)->get(route('ventas.receipts'));
        $responseReceipts->assertStatus(200);
        $responseReceipts->assertSee('Historial de Recibos Emitidos');
        $responseReceipts->assertSee('REC-');
        $responseReceipts->assertSee('250.00'); // Efectivo histórico acumulado
        $responseReceipts->assertSee('36.00');  // Descuento en leche acumulado
    }

    public function test_sidebar_has_ventas_and_recibos_sections_and_receipts_page_works()
    {
        $seller = User::where('role', 'personal_venta')->first();

        $response = $this->actingAs($seller)->get('/ventas');
        $response->assertStatus(200);

        // Validar que en el layout existen los dos apartados solicitados: Ventas y Recibos
        $response->assertSee('<span>Ventas</span>', false);
        $response->assertSee('<span>Recibos</span>', false);

        // Validar que la vista de Recibos funciona correctamente
        $responseReceipts = $this->actingAs($seller)->get(route('ventas.receipts'));
        $responseReceipts->assertStatus(200);
        $responseReceipts->assertSee('Historial de Recibos Emitidos');
        $responseReceipts->assertSee('Total Recibos');
    }

    public function test_quality_inspector_can_store_lactoscan_analysis_with_null_or_missing_optional_fields()
    {
        $inspector = User::where('role', 'inspector_calidad')->first();
        $producer = User::where('role', 'productor')->first();

        // Exact payload sent by the user where temperature and notes are omitted
        $response = $this->actingAs($inspector)->post('/calidad/analisis', [
            'producer_id' => $producer->id,
            'analysis_date' => '2026-09-14',
            'fat_percentage' => null,
            'snf_percentage' => null,
            'density' => null,
            'protein_percentage' => null,
            'water_addition_percentage' => null,
            'ph_or_acidity' => null,
            'verdict' => 'conforme',
            'scheduled_date' => '2026-09-16',
            'visit_reason' => null,
            // temperature and notes intentionally missing
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('lactoscan_analyses', [
            'producer_id' => $producer->id,
            'verdict' => 'conforme',
            'temperature' => null,
            'notes' => null,
        ]);
    }

    public function test_quality_inspector_can_store_analysis_with_temperature_notes_and_auto_schedule_visit()
    {
        $inspector = User::where('role', 'inspector_calidad')->first();
        $producer = User::where('role', 'productor')->first();

        $response = $this->actingAs($inspector)->post('/calidad/analisis', [
            'producer_id' => $producer->id,
            'analysis_date' => '2026-09-14',
            'fat_percentage' => 3.55,
            'snf_percentage' => 8.40,
            'density' => 1.029,
            'protein_percentage' => 3.20,
            'water_addition_percentage' => 0.00,
            'temperature' => 14.5,
            'ph_or_acidity' => 22.0,
            'verdict' => 'acidez_alta',
            'notes' => 'Acidez elevada detectada en control de calidad matutino.',
            'scheduled_date' => '2026-09-16',
            'visit_reason' => 'Inspección de tanque de enfriamiento.',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('lactoscan_analyses', [
            'producer_id' => $producer->id,
            'verdict' => 'acidez_alta',
            'temperature' => 14.5,
            'notes' => 'Acidez elevada detectada en control de calidad matutino.',
        ]);

        $this->assertDatabaseHas('technical_visits', [
            'producer_id' => $producer->id,
            'status' => 'programada',
            'scheduled_date' => '2026-09-16',
        ]);
    }

    public function test_get_method_on_calidad_analisis_redirects_safely_to_calidad_index()
    {
        $inspector = User::where('role', 'inspector_calidad')->first();

        // Al acceder por GET o recargar F5 en /calidad/analisis no debe dar 405 MethodNotAllowed
        $response = $this->actingAs($inspector)->get('/calidad/analisis');
        $response->assertRedirect(route('calidad.index'));

        $followResponse = $this->actingAs($inspector)->get(route('calidad.index'));
        $followResponse->assertStatus(200);
        $followResponse->assertSee('Control de Calidad Lactoscan');
    }

    public function test_quality_dashboard_shows_zones_acopio_today_visits_and_verdict_filters()
    {
        $inspector = User::where('role', 'inspector_calidad')->first();
        $producer = User::where('role', 'productor')->first();
        $zone = Zone::first();

        // Crear una ruta y registro de acopio hoy para verificar el cruce de datos
        $acopiador = User::where('role', 'acopiador')->first();
        $route = CollectionRoute::firstOrCreate(
            ['date' => app(JornadaOperativa::class)->fecha(), 'zone_id' => $zone->id],
            ['collector_id' => $acopiador->id, 'start_time' => '04:30:00', 'status' => 'asignada']
        );
        $record = CollectionRecord::updateOrCreate(
            ['collection_route_id' => $route->id, 'producer_id' => $producer->id],
            ['liters' => 55.5, 'collected_at' => '06:30:00']
        );

        // Crear una visita técnica para el día de hoy con su análisis
        $ana = LactoscanAnalysis::create([
            'producer_id' => $producer->id,
            'inspector_id' => $inspector->id,
            'analysis_date' => date('Y-m-d'),
            'verdict' => 'conforme',
        ]);

        $visit = TechnicalVisit::create([
            'lactoscan_analysis_id' => $ana->id,
            'producer_id' => $producer->id,
            'inspector_id' => $inspector->id,
            'scheduled_date' => app(JornadaOperativa::class)->fecha(),
            'scheduled_time' => '10:00:00',
            'status' => 'programada',
            'reason' => 'Revisión preventiva de acidez en finca',
        ]);

        $response = $this->actingAs($inspector)->get(route('calidad.index'));
        $response->assertStatus(200);

        // 1. Selector de 4 zonas y cruce con acopio
        $response->assertSee('1. Filtrar por Zona de Huata');
        $response->assertSee('Acopiado hoy: 55.5 L');
        $response->assertSee('Z-1');
        $response->assertSee('Z-4');

        // 2. Filtros de Veredicto (Todos, Conformes, Acidez, Adulteración)
        $response->assertSee('Conformes');
        $response->assertSee('Acidez Alta');
        $response->assertSee('Adulterada (agua)');

        // 3. Registro de Visitas y Citas Técnicas del Día
        $response->assertSee('Agenda de Visitas Técnicas de Hoy');
        $response->assertSee('Revisión preventiva de acidez en finca');
        $response->assertSee('Marcar Realizada');
    }

    public function test_quality_analyses_filtering_by_verdict_and_completing_today_visit()
    {
        $inspector = User::where('role', 'inspector_calidad')->first();
        $producer = User::where('role', 'productor')->first();

        // 1. Crear análisis de acidez y análisis conforme
        $anaAcidez = LactoscanAnalysis::create([
            'producer_id' => $producer->id,
            'inspector_id' => $inspector->id,
            'analysis_date' => date('Y-m-d'),
            'ph_or_acidity' => 24.0,
            'verdict' => 'acidez_alta',
        ]);

        $anaConforme = LactoscanAnalysis::create([
            'producer_id' => $producer->id,
            'inspector_id' => $inspector->id,
            'analysis_date' => date('Y-m-d'),
            'ph_or_acidity' => 17.0,
            'verdict' => 'conforme',
        ]);

        // 2. Filtrar por veredicto acidez_alta
        $responseAcidez = $this->actingAs($inspector)->get('/calidad?verdict=acidez_alta');
        $responseAcidez->assertStatus(200);
        $responseAcidez->assertSee('Acidez Alta');

        // 3. Crear y completar una visita técnica del día
        $visit = TechnicalVisit::create([
            'lactoscan_analysis_id' => $anaAcidez->id,
            'producer_id' => $producer->id,
            'inspector_id' => $inspector->id,
            'scheduled_date' => app(JornadaOperativa::class)->fecha(),
            'scheduled_time' => '11:30:00',
            'status' => 'programada',
            'reason' => 'Verificación de higiene por acidez alta',
        ]);

        $responseComplete = $this->actingAs($inspector)->post("/calidad/cita/{$visit->id}/completar", [
            'resolution_report' => 'Se verificó lavado de cantinas con agua caliente y cloro. Parámetro corregido.',
        ]);

        $responseComplete->assertRedirect(route('calidad.index'));
        $responseComplete->assertSessionHas('success');

        $this->assertDatabaseHas('technical_visits', [
            'id' => $visit->id,
            'status' => 'realizada',
            'resolution_report' => 'Se verificó lavado de cantinas con agua caliente y cloro. Parámetro corregido.',
        ]);
    }

    public function test_pagador_campo_dashboard_redirects_and_sidebar_is_strictly_isolated()
    {
        $pagador = User::where('role', 'pagador_campo')->first();
        if (! $pagador) {
            $pagador = User::factory()->create([
                'name' => 'Felipe Pagador Test',
                'email' => 'pagador.test@milkflow.com',
                'role' => 'pagador_campo',
                'password' => bcrypt('password'),
            ]);
        }

        // 1. Redirección automática desde /dashboard a /pagos/ruta
        $responseDashboard = $this->actingAs($pagador)->get('/dashboard');
        $responseDashboard->assertRedirect(route('pagos.ruta.index'));

        // 2. Cargar la vista de /pagos/ruta y comprobar aislamiento estricto "solo de pagos"
        $response = $this->actingAs($pagador)->get('/pagos/ruta');
        $response->assertStatus(200);

        // Debe contener los elementos de pagos en ruta
        $response->assertSee('Planilla de Sobres');
        $response->assertSee('Historial de Pagos');
        $response->assertSee('PAGOS EN RUTA');
        $response->assertSee('Efectivo en Custodia (Camioneta)');

        // NO debe contener módulos ni accesos ajenos a pagos
        $response->assertDontSee('Acopio 4:30 AM');
        $response->assertDontSee('Caudalímetro');
        $response->assertDontSee('Quesería (-10L)');
        $response->assertDontSee('Lactoscan & Citas');
        $response->assertDontSee('Tarifas & Precios');
    }

    public function test_pagador_campo_can_deliver_envelope_cash_and_generate_receipt()
    {
        $pagador = User::where('role', 'pagador_campo')->first();
        if (! $pagador) {
            $pagador = User::factory()->create([
                'name' => 'Felipe Pagador Test 2',
                'email' => 'pagador2@milkflow.com',
                'role' => 'pagador_campo',
                'password' => bcrypt('password'),
            ]);
        }

        $collector = User::where('role', 'acopiador')->first();
        $zone = Zone::first();

        $producer = User::factory()->create([
            'name' => 'Productor Viernes Test',
            'email' => 'prod.viernes@milkflow.com',
            'role' => 'productor',
            'zone_id' => $zone->id,
            'dni' => '78912345',
        ]);

        $route = CollectionRoute::firstOrCreate(
            ['date' => app(JornadaOperativa::class)->fecha(), 'zone_id' => $zone->id],
            [
                'collector_id' => $collector->id,
                'start_time' => '04:30:00',
                'status' => 'en_ruta',
                'total_collected_liters' => 50.0,
            ]
        );

        // Registro de 50 litros de leche para el ciclo
        CollectionRecord::create([
            'collection_route_id' => $route->id,
            'producer_id' => $producer->id,
            'liters' => 50.0,
            'collection_time' => '05:00:00',
        ]);

        // Compra de queso a descontar: 2 quesos a S/ 20 c/u = S/ 40
        ProducerDeduction::create([
            'producer_id' => $producer->id,
            'settlement_id' => null,
            'amount' => 40.0,
            'concept' => 'Compra 2 Quesos a cuenta de liquidación semanal',
            'status' => 'pendiente',
            'date' => app(JornadaOperativa::class)->fecha(),
        ]);

        // 1. ANTES DE AUTORIZACIÓN:
        // El pagador ve la planilla pero el efectivo NO figura y la entrega está bloqueada
        $responsePlanillaPre = $this->actingAs($pagador)->get('/pagos/ruta');
        $responsePlanillaPre->assertStatus(200);
        $responsePlanillaPre->assertSee('— Sin Autorizar');

        // Intento de entrega sin autorización debe ser rechazado por el backend
        $responsePayUnauthorized = $this->actingAs($pagador)->post("/pagos/ruta/pagar/{$producer->id}", [
            'notes' => 'Intento sin autorización',
        ]);
        $responsePayUnauthorized->assertRedirect(route('pagos.ruta.index'));
        $responsePayUnauthorized->assertSessionHas('error');

        // 2. EL ADMINISTRADOR AUTORIZA EL PAGO:
        $admin = User::where('role', 'admin')->first();
        $responseAuth = $this->actingAs($admin)->post("/admin/pagos/autorizar/{$producer->id}");
        $responseAuth->assertSessionHas('success');

        // 3. DESPUÉS DE AUTORIZACIÓN:
        // El pagador ahora ve el sobre listo y el botón de entrega habilitado
        $responsePlanillaPost = $this->actingAs($pagador)->get('/pagos/ruta');
        $responsePlanillaPost->assertStatus(200);
        $responsePlanillaPost->assertSee('Listo en Sobre');
        $responsePlanillaPost->assertSee('Entregar Sobre');

        // El pagador ejecuta la entrega del sobre en efectivo en ruta
        $responsePay = $this->actingAs($pagador)->post("/pagos/ruta/pagar/{$producer->id}", [
            'notes' => 'Sobre entregado en efectivo en ruta de acopio del viernes',
        ]);

        // Debe registrar la entrega y redirigir con éxito a la planilla
        $settlement = ProducerSettlement::where('producer_id', $producer->id)
            ->where('status', 'pagado')
            ->first();

        $this->assertNotNull($settlement, 'La liquidación debió marcarse como pagado tras entrega física');
        $responsePay->assertRedirect(route('pagos.ruta.index'));
        $responsePay->assertSessionHas('success');

        // Verificar datos del sobre en BD
        $this->assertEquals($pagador->id, $settlement->paid_by);
        $this->assertEquals(50.0, $settlement->total_liters);
        $this->assertEquals(40.0, $settlement->deductions_total);
        $this->assertGreaterThan(0, $settlement->net_total);

        // Verificar vista del recibo térmico
        $responseReceipt = $this->actingAs($pagador)->get(route('pagos.ruta.receipt', $settlement));
        $responseReceipt->assertStatus(200);
        $responseReceipt->assertSee('Recibo de Liquidación Semanal en Sobre de Efectivo');
        $responseReceipt->assertSee('Productor Viernes Test');
        $responseReceipt->assertSee('Descuento por Compra de Quesos');
        $responseReceipt->assertSee('Total Neto en Sobre');

        // Verificar que aparece en el historial de pagos
        $responseHistory = $this->actingAs($pagador)->get('/pagos/ruta/historial');
        $responseHistory->assertStatus(200);
        $responseHistory->assertSee('Productor Viernes Test');
        $responseHistory->assertSee('#SOBRE-'.str_pad($settlement->id, 5, '0', STR_PAD_LEFT));
    }

    public function test_admin_sidebar_and_lactoscan_permissions_restriction()
    {
        $admin = User::where('role', 'admin')->first();
        $inspector = User::where('role', 'inspector_calidad')->first();

        // 1. Admin en /calidad NO ve el formulario de registro y ve el historial
        $responseAdmin = $this->actingAs($admin)->get('/calidad');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertDontSee('Registrar prueba Lactoscan');
        $responseAdmin->assertSee('Historial de evaluaciones Lactoscan');

        // 2. Admin no ve enlaces a Caudalímetro ni Quesería en el sidebar
        $responseAdmin->assertDontSee('route(\'planta.verificacion\')', false);
        $responseAdmin->assertDontSee('Caudalímetro');
        $responseAdmin->assertDontSee('Quesería (-10L)');

        // 3. Admin no puede enviar registro de análisis físico-químico
        $producer = User::where('role', 'productor')->first();
        $responseAdminPost = $this->actingAs($admin)->post('/calidad/analisis', [
            'producer_id' => $producer->id,
            'analysis_date' => date('Y-m-d'),
            'verdict' => 'conforme',
        ]);
        $responseAdminPost->assertRedirect(route('calidad.index'));
        $responseAdminPost->assertSessionHas('error');

        // 4. Inspector de Calidad SÍ ve el formulario de registro en /calidad
        $responseInspector = $this->actingAs($inspector)->get('/calidad');
        $responseInspector->assertStatus(200);
        $responseInspector->assertSee('Registrar prueba Lactoscan');
    }

    public function test_admin_financial_cash_flow_and_sidebar_restriction()
    {
        $admin = User::where('role', 'admin')->first();
        $personalVenta = User::where('role', 'personal_venta')->first();
        $acopiador = User::where('role', 'acopiador')->first();

        // 1. Admin en sidebar ve 'Flujo de Caja' y 'Recibos', pero NO 'Ventas' ni 'Sobres en Ruta'
        $responseAdmin = $this->actingAs($admin)->get('/admin/finanzas');
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Flujo de Caja');
        $responseAdmin->assertSee('Recibos');
        $responseAdmin->assertSee('Autorizar Pagos');
        $responseAdmin->assertDontSee('Sobres en Ruta');

        // 2. Si el Admin intenta acceder a /ventas (mostrador POS), es redirigido a /admin/finanzas
        $responseVentas = $this->actingAs($admin)->get('/ventas');
        $responseVentas->assertRedirect(route('admin.finanzas.index'));

        // 3. El Admin ve las métricas de ingresos, egresos de proveedores y egresos de personal
        $responseAdmin->assertSee('Ingresos (Ventas)');
        $responseAdmin->assertSee('Proveedores Leche');
        $responseAdmin->assertSee('Personal y Gastos');
        $responseAdmin->assertSee('Balance Operativo Neto');

        // 4. El Admin puede registrar un nuevo egreso de personal / operativo
        $responseExpense = $this->actingAs($admin)->post('/admin/finanzas/egreso', [
            'category' => 'pago_personal',
            'description' => 'Pago semanal de turno acopiador',
            'amount' => 280.00,
            'expense_date' => date('Y-m-d'),
            'user_id' => $acopiador->id,
            'payment_method' => 'efectivo',
            'receipt_number' => 'REC-PAGO-001',
            'notes' => 'Pago verificado por administración',
        ]);

        $responseExpense->assertRedirect(route('admin.finanzas.index'));
        $responseExpense->assertSessionHas('success');

        $this->assertDatabaseHas('operational_expenses', [
            'description' => 'Pago semanal de turno acopiador',
            'amount' => 280.00,
            'category' => 'pago_personal',
            'user_id' => $acopiador->id,
        ]);

        // 5. Personal de venta SÍ tiene acceso a /ventas (caja)
        $responseVentaUser = $this->actingAs($personalVenta)->get('/ventas');
        $responseVentaUser->assertStatus(200);
        $responseVentaUser->assertSee('Ventas');
    }

    /** Un pedido de queso con el formato de renglones que usa la caja. */
    private function pedidoDeQueso(float $cantidad): array
    {
        return [[
            'product_id' => Product::where('item_code', 'CHEESE_MOLD_UNITS')->value('id'),
            'quantity' => $cantidad,
        ]];
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
