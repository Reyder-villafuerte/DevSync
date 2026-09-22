<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\User;
use App\Services\Acopio\JornadaOperativa;
use App\Services\Ventas\VentaService;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La aplicación vive en la hora de Huata, no en UTC.
 *
 * Con la app en UTC, entre las 19:00 y la medianoche de Puno el servidor ya
 * estaba en el día siguiente: las pantallas del día buscaban la fecha
 * equivocada y la caja aparecía vacía con ventas recién hechas.
 */
class JornadaHorariaTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_aplicacion_corre_en_la_hora_de_huata(): void
    {
        $this->assertSame('America/Lima', config('app.timezone'));
    }

    public function test_a_las_diez_de_la_noche_la_app_sigue_en_el_mismo_dia_que_huata(): void
    {
        // 2026-09-20 22:45 en Puno son las 03:45 del 21 en UTC. Con la app en
        // UTC, a esta hora el servidor ya estaba en el día siguiente.
        $this->travelTo('2026-09-20 22:45:00');

        $this->assertSame('2026-09-20', now()->toDateString());
        $this->assertSame('2026-09-20', app(JornadaOperativa::class)->fecha());

        // Ojo: aquí NO se puede asertar sobre date(), que lee el reloj real y
        // no se deja congelar. Esa es justamente la razón por la que ninguna
        // pantalla del día la usa ya.
        $this->assertSame(now()->toDateString(), app(JornadaOperativa::class)->fecha());
    }

    public function test_antes_de_las_cuatro_y_media_la_jornada_todavia_es_la_de_ayer(): void
    {
        // El camión sale 4:30 AM: lo de las 3 de la mañana es del día anterior.
        $this->travelTo('2026-09-21 03:00:00');

        $this->assertSame('2026-09-21', now()->toDateString());
        $this->assertSame('2026-09-20', app(JornadaOperativa::class)->fecha());
    }

    public function test_una_venta_de_la_noche_sigue_apareciendo_en_la_caja_del_dia(): void
    {
        $this->travelTo('2026-09-20 22:45:00');
        $this->seed(MilkFlowHuataSeeder::class);

        $vendedor = User::where('role', 'personal_venta')->firstOrFail();
        $queso = Product::where('item_code', 'CHEESE_MOLD_UNITS')->firstOrFail();
        InventoryStock::adjustStock('CHEESE_MOLD_UNITS', 10);

        $venta = app(VentaService::class)->registrarVenta($vendedor, [
            'customer_id' => Customer::where('type', 'local')->firstOrFail()->id,
            'items' => [['product_id' => $queso->id, 'quantity' => 1]],
        ]);

        $this->actingAs($vendedor)
            ->get(route('ventas.index'))
            ->assertOk()
            ->assertViewHas('ventasHoy', fn ($ventas) => $ventas->contains('id', $venta->id));
    }
}
