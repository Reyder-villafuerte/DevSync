<?php

namespace Tests\Feature;

use App\Models\CollectionRecord;
use App\Models\CollectionRoute;
use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Sale;
use App\Models\SystemPrice;
use App\Models\User;
use App\Models\Zone;
use App\Services\Acopio\JornadaOperativa;
use Carbon\Carbon;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizedOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);
        Carbon::setTestNow(Carbon::parse('2026-09-16 18:00:00', 'America/Lima'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_jornada_boundary(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 04:29:00', 'America/Lima'));
        $this->assertSame('2026-09-15', app(JornadaOperativa::class)->fecha());
        Carbon::setTestNow(Carbon::parse('2026-09-16 04:30:00', 'America/Lima'));
        $this->assertSame('2026-09-16', app(JornadaOperativa::class)->fecha());
    }

    public function test_assignment_persists_and_is_authorized(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $collectors = User::where('role', 'acopiador')->take(2)->get();
        $zone = Zone::firstOrFail();
        $route = CollectionRoute::firstOrCreate(['date' => '2026-09-16', 'zone_id' => $zone->id], ['collector_id' => $collectors[0]->id]);
        $this->actingAs($admin)->post('/acopio/asignar', ['date' => '2026-09-16', 'zone_id' => $zone->id, 'collector_id' => $collectors[1]->id, 'start_time' => '04:30'])->assertSessionHas('success');
        $this->assertSame($collectors[1]->id, $route->fresh()->collector_id);
        $this->assertSame(1, CollectionRoute::where('date', '2026-09-16')->where('zone_id', $zone->id)->count());
        $this->assertSame('acopiador', $collectors[1]->fresh()->role);
        $this->actingAs($collectors[1])->get('/acopio')->assertSee($zone->name);
        $this->actingAs($collectors[0])->post('/acopio/asignar', ['date' => '2026-09-16', 'zone_id' => $zone->id, 'collector_id' => $collectors[0]->id])->assertForbidden();
    }

    public function test_price_validation_and_active_price(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $data = ['season_name' => 'Nueva temporada', 'price_milk_base' => '1.40', 'price_milk_water_penalty_low' => '1.20', 'price_milk_water_penalty_high' => '0.90', 'price_cheese_provider' => '21.25', 'price_cheese_wholesale' => '22.00', 'price_cheese_local' => '23.00'];
        foreach (['0', '-1', 'texto', '1.234'] as $invalid) {
            $this->actingAs($admin)->post('/admin/precios', array_replace($data, ['price_cheese_provider' => $invalid]))->assertSessionHasErrors('price_cheese_provider');
        }
        $this->actingAs($admin)->post('/admin/precios', $data)->assertSessionHas('success');
        $this->assertSame('21.25', SystemPrice::current()->price_cheese_provider);
        $this->assertSame(1, SystemPrice::where('is_active', true)->count());
        $this->actingAs($admin)->get('/admin/precios')->assertSee('21.25');
    }

    public function test_provider_is_registered_once_and_route_stays_open(): void
    {
        $collector = User::where('role', 'acopiador')->firstOrFail();
        $zone = Zone::firstOrFail();
        $producers = $zone->producers()->take(2)->get();
        $route = CollectionRoute::firstOrCreate(['date' => '2026-09-16', 'zone_id' => $zone->id], ['collector_id' => $collector->id]);
        $collector = $route->collector;
        $route->records()->delete();
        $route->update(['status' => 'asignada']);
        $url = '/acopio/ruta/'.$route->id.'/entrega';
        $this->actingAs($collector)->postJson($url, ['producer_id' => $producers[0]->id, 'liters' => '12.50', 'notes' => 'Campo'])->assertOk()->assertJsonPath('record.notes', 'Campo');
        $this->actingAs($collector)->postJson($url, ['producer_id' => $producers[0]->id, 'liters' => '14.00'])->assertStatus(422)->assertJsonPath('message', 'Este proveedor ya fue registrado en la jornada actual.');
        $this->assertSame(1, CollectionRecord::where('collection_route_id', $route->id)->count());
        $this->actingAs($collector)->postJson($url, ['producer_id' => $producers[1]->id, 'liters' => '10.00'])->assertOk();
        $this->assertSame('en_ruta', $route->fresh()->status);
    }

    public function test_new_jornada_reenables_provider_and_wrong_collector_is_forbidden(): void
    {
        $zone = Zone::firstOrFail();
        $collector = User::where('role', 'acopiador')->firstOrFail();
        $provider = $zone->producers()->firstOrFail();
        $route = CollectionRoute::firstOrCreate(['date' => '2026-09-16', 'zone_id' => $zone->id], ['collector_id' => $collector->id]);
        $route->records()->delete();
        $route->update(['status' => 'asignada']);
        $collector = $route->collector;
        $wrongCollector = User::where('role', 'acopiador')->whereKeyNot($collector->id)->firstOrFail();

        $this->actingAs($wrongCollector)->postJson('/acopio/ruta/'.$route->id.'/entrega', ['producer_id' => $provider->id, 'liters' => '5.00'])->assertForbidden();
        $this->actingAs($collector)->postJson('/acopio/ruta/'.$route->id.'/entrega', ['producer_id' => $provider->id, 'liters' => '5.00'])->assertOk();
        Carbon::setTestNow(Carbon::parse('2026-09-17 04:30:00', 'America/Lima'));
        $newRoute = CollectionRoute::firstOrCreate(['date' => '2026-09-17', 'zone_id' => $zone->id], ['collector_id' => $collector->id]);
        $this->actingAs($collector)->postJson('/acopio/ruta/'.$newRoute->id.'/entrega', ['producer_id' => $provider->id, 'liters' => '6.00'])->assertOk();
        $this->assertSame(1, CollectionRecord::where('producer_id', $provider->id)->where('collection_route_id', $route->id)->count());
        $this->assertSame(1, CollectionRecord::where('producer_id', $provider->id)->where('collection_route_id', $newRoute->id)->count());
    }

    public function test_new_sale_uses_updated_price_and_old_sale_keeps_original(): void
    {
        $seller = User::where('role', 'personal_venta')->firstOrFail();
        $admin = User::where('role', 'admin')->firstOrFail();
        $customer = Customer::where('type', 'local')->firstOrFail();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 2);
        $saleData = ['customer_id' => $customer->id, 'cheese_molds_quantity' => 1, 'unit_price' => '0.01', 'total_amount' => '0.01'];
        $this->actingAs($seller)->post('/ventas', $saleData)->assertRedirect();
        $oldSale = Sale::orderByDesc('id')->firstOrFail();

        $priceData = ['season_name' => 'Nueva temporada', 'price_milk_base' => '1.40', 'price_milk_water_penalty_low' => '1.20', 'price_milk_water_penalty_high' => '0.90', 'price_cheese_provider' => '21.25', 'price_cheese_wholesale' => '22.00', 'price_cheese_local' => '23.00'];
        $this->actingAs($admin)->post('/admin/precios', $priceData)->assertSessionHas('success');
        $this->actingAs($seller)->post('/ventas', $saleData)->assertRedirect();
        $newSale = Sale::orderByDesc('id')->firstOrFail();
        $this->assertSame(23.0, (float) $newSale->unit_price);
        $this->assertSame(23.0, (float) $newSale->total_amount);
        $this->assertSame(20.0, (float) $oldSale->fresh()->unit_price);
        $this->assertSame(20.0, (float) $oldSale->fresh()->total_amount);
    }
}
