<?php

namespace Tests\Feature;

use App\Models\CollectionRecord;
use App\Models\CollectionRoute;
use App\Models\InventoryStock;
use App\Models\LactoscanAnalysis;
use App\Models\ProducerDeduction;
use App\Models\ProducerSettlement;
use App\Models\Sale;
use App\Models\SyncOperation;
use App\Models\User;
use App\Models\Zone;
use App\Services\Acopio\JornadaOperativa;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * API de sincronización offline-first de MilkFlowMovil.
 *
 * Lo que se protege aquí: que el dispositivo solo reciba lo que su rol puede
 * ver, que reenviar una operación tras un corte de señal no duplique datos y
 * que las reglas de negocio del web se apliquen igual desde el móvil.
 */
class SincronizacionMovilTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);
    }

    private function acopiador(): User
    {
        return User::where('role', 'acopiador')->orderBy('id')->first();
    }

    private function productor(): User
    {
        return User::where('role', 'productor')->orderBy('id')->first();
    }

    private function operacion(string $comando, array $payload = [], ?string $uuid = null): array
    {
        return [
            'client_uuid' => $uuid ?: (string) Str::uuid(),
            'comando' => $comando,
            'payload' => $payload,
        ];
    }

    public function test_login_movil_acepta_dni_y_devuelve_token(): void
    {
        $acopiador = $this->acopiador();

        $respuesta = $this->postJson('/api/sync/login', [
            'usuario' => $acopiador->dni,
            'password' => 'password',
            'device_id' => 'tablet-ruta-01',
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('usuario.role', 'acopiador');
        $this->assertNotEmpty($respuesta->json('token'));
    }

    public function test_login_movil_rechaza_credenciales_invalidas(): void
    {
        $this->postJson('/api/sync/login', [
            'usuario' => $this->acopiador()->dni,
            'password' => 'clave-equivocada',
        ])->assertStatus(401);
    }

    public function test_el_productor_solo_baja_sus_propias_entregas(): void
    {
        $productorA = $this->productor();
        $productorB = User::where('role', 'productor')->where('id', '!=', $productorA->id)->first();
        $zona = Zone::first();

        $ruta = CollectionRoute::updateOrCreate(
            ['date' => date('Y-m-d'), 'zone_id' => $zona->id],
            [
                'collector_id' => $this->acopiador()->id,
                'start_time' => '04:30:00',
                'status' => 'en_ruta',
                'total_collected_liters' => 0,
            ]
        );

        CollectionRecord::where('collection_route_id', $ruta->id)->delete();

        CollectionRecord::create(['collection_route_id' => $ruta->id, 'producer_id' => $productorA->id, 'liters' => 12]);
        CollectionRecord::create(['collection_route_id' => $ruta->id, 'producer_id' => $productorB->id, 'liters' => 20]);

        $respuesta = $this->actingAs($productorA, 'sanctum')
            ->postJson('/api/sync/pull', ['entidades' => ['collection_records']]);

        $respuesta->assertOk();
        $filas = $respuesta->json('entidades.collection_records.filas');

        $this->assertNotEmpty($filas);
        foreach ($filas as $fila) {
            $this->assertSame($productorA->id, $fila['producer_id'], 'Ninguna entrega ajena puede llegar al dispositivo.');
        }
        $this->assertContains(12.0, array_map(fn ($f) => (float) $f['liters'], $filas));
    }

    public function test_el_acopiador_no_recibe_liquidaciones_de_nadie(): void
    {
        $respuesta = $this->actingAs($this->acopiador(), 'sanctum')->postJson('/api/sync/pull');

        $respuesta->assertOk();
        $this->assertArrayNotHasKey('producer_settlements', $respuesta->json('entidades'));
        $this->assertArrayHasKey('collection_routes', $respuesta->json('entidades'));
    }

    public function test_la_bajada_incremental_solo_trae_lo_cambiado_despues_del_cursor(): void
    {
        // Sin margen de reloj el corte es exacto; en producción el margen de 2 s
        // reenvía a propósito unas pocas filas ya conocidas (el móvil las reescribe).
        config(['sync.margen_cursor_segundos' => 0]);

        $acopiador = $this->acopiador();

        $primera = $this->actingAs($acopiador, 'sanctum')
            ->postJson('/api/sync/pull', ['entidades' => ['zones']]);
        $primera->assertOk();

        $cursor = $primera->json('entidades.zones.cursor');
        $this->assertNotNull($cursor);

        $segunda = $this->actingAs($acopiador, 'sanctum')
            ->postJson('/api/sync/pull', ['entidades' => ['zones'], 'cursores' => ['zones' => $cursor]]);

        $segunda->assertOk();
        $this->assertCount(0, $segunda->json('entidades.zones.filas'));

        // Un cambio posterior sí debe viajar (el reloj avanza para salir del
        // segundo del cursor, que es la precisión con la que se guarda la fecha).
        $this->travel(2)->seconds();
        Zone::first()->touch();

        $tercera = $this->actingAs($acopiador, 'sanctum')
            ->postJson('/api/sync/pull', ['entidades' => ['zones'], 'cursores' => ['zones' => $cursor]]);

        $this->assertGreaterThanOrEqual(1, count($tercera->json('entidades.zones.filas')));
    }

    public function test_la_subida_registra_la_entrega_y_crea_la_ruta_del_dia(): void
    {
        $acopiador = $this->acopiador();
        $productor = $this->productor();
        $uuidRuta = (string) Str::uuid();

        $respuesta = $this->actingAs($acopiador, 'sanctum')->postJson('/api/sync/push', [
            'device_id' => 'tablet-ruta-01',
            'operaciones' => [
                $this->operacion('registrar_entrega', [
                    'ruta_client_uuid' => $uuidRuta,
                    'fecha' => app(JornadaOperativa::class)->fecha(),
                    'producer_id' => $productor->id,
                    'liters' => 18.5,
                    'collected_at' => '04:45:00',
                ]),
            ],
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('resultados.0.estado', 'aplicada');

        $this->assertDatabaseHas('collection_records', [
            'producer_id' => $productor->id,
            'liters' => 18.5,
        ]);

        $ruta = CollectionRoute::where('client_uuid', $uuidRuta)->first();
        $this->assertNotNull($ruta, 'La ruta creada sin señal debe quedar ligada por su client_uuid.');
        $this->assertEquals(18.5, (float) $ruta->total_collected_liters);
    }

    public function test_reenviar_la_misma_operacion_no_duplica_la_entrega(): void
    {
        $acopiador = $this->acopiador();
        $productor = $this->productor();
        $operacion = $this->operacion('registrar_entrega', [
            'ruta_client_uuid' => (string) Str::uuid(),
            'fecha' => app(JornadaOperativa::class)->fecha(),
            'producer_id' => $productor->id,
            'liters' => 22.0,
        ]);

        $cuerpo = ['device_id' => 'tablet-ruta-01', 'operaciones' => [$operacion]];

        $this->actingAs($acopiador, 'sanctum')->postJson('/api/sync/push', $cuerpo)->assertOk();
        $segunda = $this->actingAs($acopiador, 'sanctum')->postJson('/api/sync/push', $cuerpo);

        $segunda->assertOk();
        $segunda->assertJsonPath('resultados.0.repetida', true);

        $this->assertSame(1, CollectionRecord::where('producer_id', $productor->id)->where('liters', 22.0)->count());
        $this->assertSame(1, SyncOperation::where('client_uuid', $operacion['client_uuid'])->count());
    }

    public function test_un_rol_no_puede_ejecutar_comandos_de_otro(): void
    {
        $respuesta = $this->actingAs($this->productor(), 'sanctum')->postJson('/api/sync/push', [
            'operaciones' => [
                $this->operacion('producir_queso', ['cheese_molds_produced' => 5]),
            ],
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('resultados.0.estado', 'rechazada');
        $this->assertDatabaseCount('cheese_productions', 0);
    }

    public function test_produccion_sin_stock_se_rechaza_y_no_se_reintenta(): void
    {
        $jefe = User::where('role', 'jefe_produccion')->first();
        InventoryStock::adjustStock('MILK_RAW_LITERS', -InventoryStock::getStock('MILK_RAW_LITERS'));

        $respuesta = $this->actingAs($jefe, 'sanctum')->postJson('/api/sync/push', [
            'operaciones' => [$this->operacion('producir_queso', ['cheese_molds_produced' => 3])],
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('resultados.0.estado', 'rechazada');
        $this->assertStringContainsString('Stock de leche insuficiente', $respuesta->json('resultados.0.mensaje'));
    }

    public function test_la_venta_desde_el_movil_descuenta_stock_y_emite_recibo(): void
    {
        $vendedor = User::where('role', 'personal_venta')->first();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 50, 'Moldes de Queso Madurado Huata', 'moldes');
        $stockPrevio = InventoryStock::getStock('CHEESE_MOLD_UNITS');

        $respuesta = $this->actingAs($vendedor, 'sanctum')->postJson('/api/sync/push', [
            'operaciones' => [
                $this->operacion('registrar_venta', [
                    'new_first_name' => 'Cliente',
                    'new_last_name' => 'De Ruta',
                    'new_type' => 'local',
                    'cheese_molds_quantity' => 2,
                    'payment_method' => 'efectivo',
                ]),
            ],
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('resultados.0.estado', 'aplicada');

        $this->assertEquals($stockPrevio - 2, InventoryStock::getStock('CHEESE_MOLD_UNITS'));
        $this->assertNotEmpty($respuesta->json('resultados.0.datos.receipt_number'));
        $this->assertSame(1, Sale::whereNotNull('client_uuid')->count());
    }

    public function test_el_pagador_no_puede_entregar_un_sobre_sin_autorizacion(): void
    {
        $pagador = User::where('role', 'pagador_campo')->first();
        $productor = $this->productor();
        ProducerSettlement::where('producer_id', $productor->id)->delete();

        $respuesta = $this->actingAs($pagador, 'sanctum')->postJson('/api/sync/push', [
            'operaciones' => [$this->operacion('entregar_sobre', ['producer_id' => $productor->id])],
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('resultados.0.estado', 'rechazada');
        $this->assertStringContainsString('no ha sido autorizado', $respuesta->json('resultados.0.mensaje'));
        $this->assertSame(0, ProducerSettlement::where('producer_id', $productor->id)->count());
    }

    public function test_la_penalidad_por_agua_se_aplica_a_toda_la_semana_al_autorizar(): void
    {
        $admin = User::where('role', 'admin')->first();
        $inspector = User::where('role', 'inspector_calidad')->first();
        $productor = $this->productor();
        $zona = Zone::first();

        CollectionRecord::where('producer_id', $productor->id)->delete();
        ProducerDeduction::where('producer_id', $productor->id)->delete();
        LactoscanAnalysis::where('producer_id', $productor->id)->delete();

        $ruta = CollectionRoute::updateOrCreate(
            ['date' => date('Y-m-d'), 'zone_id' => $zona->id],
            [
                'collector_id' => $this->acopiador()->id,
                'start_time' => '04:30:00',
                'status' => 'verificada',
                'total_collected_liters' => 100,
            ]
        );
        CollectionRecord::create(['collection_route_id' => $ruta->id, 'producer_id' => $productor->id, 'liters' => 100]);

        LactoscanAnalysis::create([
            'producer_id' => $productor->id,
            'inspector_id' => $inspector->id,
            'analysis_date' => date('Y-m-d'),
            'water_addition_percentage' => 3.0,
            'verdict' => 'adulterada',
        ]);

        $respuesta = $this->actingAs($admin, 'sanctum')->postJson('/api/sync/push', [
            'operaciones' => [$this->operacion('autorizar_pago', ['producer_id' => $productor->id])],
        ]);

        $respuesta->assertOk();
        $respuesta->assertJsonPath('resultados.0.estado', 'aplicada');

        $liquidacion = ProducerSettlement::where('producer_id', $productor->id)->latest('id')->first();

        // 100 L x S/ 1.40 = 140 bruto; penalidad de S/ 0.20/L sobre los 100 L = 20.
        $this->assertEquals(140.0, (float) $liquidacion->gross_total);
        $this->assertEquals(20.0, (float) $liquidacion->deductions_total);
        $this->assertEquals(120.0, (float) $liquidacion->net_total);
        $this->assertSame('autorizado', $liquidacion->status);
    }

    public function test_el_endpoint_de_ciclos_entrega_el_calculo_del_sobre(): void
    {
        $pagador = User::where('role', 'pagador_campo')->first();

        $respuesta = $this->actingAs($pagador, 'sanctum')->getJson('/api/sync/ciclos-pago');

        $respuesta->assertOk();
        $respuesta->assertJsonStructure(['ciclos' => [['producer_id', 'liters', 'net', 'authorized']]]);
    }

    public function test_la_bajada_exige_autenticacion(): void
    {
        $this->postJson('/api/sync/pull')->assertStatus(401);
    }
}
