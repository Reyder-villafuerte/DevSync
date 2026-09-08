<?php

namespace Tests\Feature;

use App\Models as M;
use App\Services as S;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->withoutVite();
    }

    private function user(string $role): M\User
    {
        return M\User::where('email', $role.'@milkflow.test')->firstOrFail();
    }

    private function delivery(): M\Entrega
    {
        $u = $this->user('acopiador');
        $this->actingAs($u);

        return app(S\DeliveryService::class)->create(['uuid' => (string) \Str::uuid(), 'productor_id' => M\Productor::firstOrFail()->id, 'tipo' => 'RECOGIDA', 'ruta_id' => M\Ruta::firstOrFail()->id, 'litros' => 100], $u);
    }

    public function test_all_six_dashboards_and_allowed_lists_render(): void
    {
        foreach (['admin' => ['dashboard', 'users', 'solicitudes', 'producers', 'zones', 'routes', 'audit'], 'acopiador' => ['dashboard', 'producers', 'entregas', 'entregas/create'], 'supervisor' => ['dashboard', 'calidad', 'calidad/create', 'problemas'], 'produccion' => ['dashboard', 'lotes', 'lotes/create'], 'despacho' => ['dashboard', 'ventas', 'ventas/create', 'stock'], 'productor' => ['dashboard', 'entregas', 'calidad', 'liquidaciones', 'comunicados']] as $role => $paths) {
            $this->actingAs($this->user($role));
            foreach ($paths as $path) {
                $this->get('/'.$role.'/'.$path)->assertOk();
            }
        }
    }

    public function test_cross_role_urls_and_mutations_are_forbidden(): void
    {
        foreach (['acopiador', 'supervisor', 'produccion', 'despacho', 'productor'] as $role) {
            $this->actingAs($this->user($role));
            $this->get('/admin/dashboard')->assertForbidden();
            $this->post('/admin/producers', [])->assertForbidden();
        }
        $this->actingAs($this->user('productor'));
        $this->post('/productor/entregas', [])->assertForbidden();
    }

    public function test_delivery_replay_is_idempotent(): void
    {
        $d = $this->delivery();
        $count = M\Entrega::count();
        $same = app(S\DeliveryService::class)->create($d->toArray(), $this->user('acopiador'));
        $this->assertSame($d->id, $same->id);
        $this->assertSame($count, M\Entrega::count());
    }

    public function test_acid_rejects_milk_without_expelling_producer(): void
    {
        $d = $this->delivery();
        $u = $this->user('supervisor');
        $this->actingAs($u);
        app(S\QualityService::class)->create(['entrega_id' => $d->id, 'ph' => 6.4, 'temperatura' => 15, 'agua_agregada_porcentaje' => 0], $u);
        $this->assertSame('RECHAZADA', $d->fresh()->estado);
        $this->assertTrue((bool) $d->productor->fresh()->activo);
        $this->assertDatabaseHas('capacitaciones', ['entrega_id' => $d->id, 'tema' => 'Buenas Prácticas de Ordeño']);
    }

    public function test_water_recurrence_permanently_expels(): void
    {
        for ($i = 0; $i < 2; $i++) {
            $d = $this->delivery();
            $u = $this->user('supervisor');
            $this->actingAs($u);
            app(S\QualityService::class)->create(['entrega_id' => $d->id, 'ph' => 6.7, 'temperatura' => 4, 'agua_agregada_porcentaje' => 2], $u);
        }
        $this->assertFalse((bool) $d->productor->fresh()->activo);
        $this->assertNotNull($d->productor->fresh()->expulsado_at);
        $this->assertSame(2, M\Sancion::count());
    }

    public function test_five_percent_expels_immediately(): void
    {
        $d = $this->delivery();
        $u = $this->user('supervisor');
        $this->actingAs($u);
        app(S\QualityService::class)->create(['entrega_id' => $d->id, 'ph' => 6.7, 'temperatura' => 4, 'agua_agregada_porcentaje' => 5], $u);
        $this->assertFalse((bool) $d->productor->fresh()->activo);
    }

    public function test_sale_uses_server_price_and_decreases_stock(): void
    {
        $u = $this->user('despacho');
        $this->actingAs($u);
        $stock = M\StockQueso::firstOrFail();
        $before = $stock->cantidad;
        $sale = app(S\SalesService::class)->create(['uuid' => (string) \Str::uuid(), 'cliente' => 'Socio', 'tipo_cliente' => 'Productor / Socio', 'stock_queso_id' => $stock->id, 'cantidad' => 2, 'precio_unitario' => 1], $u);
        $this->assertEquals(36, $sale->total);
        $this->assertEquals($before - 2, $stock->fresh()->cantidad);
    }

    public function test_insufficient_stock_does_not_create_sale(): void
    {
        $u = $this->user('despacho');
        $this->actingAs($u);
        $before = M\Venta::count();
        try {
            app(S\SalesService::class)->create(['uuid' => (string) \Str::uuid(), 'cliente' => 'Cliente', 'tipo_cliente' => 'Mayorista', 'stock_queso_id' => M\StockQueso::firstOrFail()->id, 'cantidad' => 9999], $u);
            $this->fail('Debe rechazar la sobreventa');
        } catch (ValidationException $e) {
            $this->assertSame($before, M\Venta::count());
        }
    }

    public function test_production_cannot_use_more_conforming_milk_than_available(): void
    {
        $u = $this->user('produccion');
        $this->actingAs($u);
        $this->expectException(ValidationException::class);
        app(S\ProductionService::class)->create(['codigo_lote' => 'INVALID', 'tipo_producto' => 'Queso para fresco', 'litros_leche' => 10000, 'moldes_obtenidos' => 10], $u);
    }

    public function test_business_week_is_thursday_to_wednesday(): void
    {
        [$a,$b] = S\BusinessWeek::bounds('2026-09-07');
        $this->assertSame('2026-09-03', $a->toDateString());
        $this->assertSame('2026-09-09', $b->toDateString());
    }

    public function test_settlement_applies_weekly_penalty_once(): void
    {
        $d = $this->delivery();
        $d->update(['fecha_hora' => now()->subWeeks(2)]);
        $u = $this->user('supervisor');
        $this->actingAs($u);
        app(S\QualityService::class)->create(['entrega_id' => $d->id, 'ph' => 6.7, 'temperatura' => 4, 'agua_agregada_porcentaje' => 2], $u);
        $admin = $this->user('admin');
        $this->actingAs($admin);
        $l = app(S\SettlementService::class)->calculate($d->productor_id, $d->fecha_hora, $admin);
        $this->assertEquals(65, $l->total);
        $this->assertEquals(105, $l->penalizaciones);
        $this->expectException(ValidationException::class);
        app(S\SettlementService::class)->calculate($d->productor_id,$d->fecha_hora,$admin);
    }
}
