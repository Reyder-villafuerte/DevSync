<?php

namespace Tests\Feature;

use App\Models\InventoryStock;
use App\Models\Product;
use App\Models\ProductionOrder;
use App\Models\User;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Una tanda con varios productos sale del mismo almacén.
 *
 * Mirar cada renglón contra el stock completo deja pasar tandas que en la
 * planta no caben: si hay 60 L de leche y el queso se lleva 50, al yogur solo
 * le quedan 10.
 */
class LotesCompartenAlmacenTest extends TestCase
{
    use RefreshDatabase;

    private User $jefePlanta;

    private Product $queso;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->jefePlanta = User::where('role', 'jefe_produccion')->firstOrFail();
        $this->queso = Product::where('item_code', config('huata.codigos.queso'))->firstOrFail();
    }

    public function test_dos_renglones_juntos_no_pueden_pasarse_del_mismo_almacen(): void
    {
        $lecheAntes = $this->lecheEnAlmacen();

        // Cada molde se lleva 10 L: por separado los dos renglones caben, pero
        // sumados piden más leche de la que hay.
        $porRenglon = (int) floor($lecheAntes / 10 / 2) + 1;

        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.lotes.store'), [
                'items' => [
                    ['product_id' => $this->queso->id, 'planned_quantity' => $porRenglon],
                    ['product_id' => $this->queso->id, 'planned_quantity' => $porRenglon],
                ],
                'iniciar_ahora' => '1',
            ])
            ->assertSessionHasErrors('stock');

        // O entran los dos o no entra ninguno: nada a medias.
        $this->assertSame(0, ProductionOrder::count());
        $this->assertEqualsWithDelta($lecheAntes, $this->lecheEnAlmacen(), 0.001);
    }

    public function test_la_tanda_entra_cuando_de_verdad_alcanza(): void
    {
        $lecheAntes = $this->lecheEnAlmacen();
        $moldes = (int) floor($lecheAntes / 10);

        $primero = (int) floor($moldes / 2);
        $segundo = $moldes - $primero;

        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.lotes.store'), [
                'items' => [
                    ['product_id' => $this->queso->id, 'planned_quantity' => $primero],
                    ['product_id' => $this->queso->id, 'planned_quantity' => $segundo],
                ],
                'iniciar_ahora' => '1',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(2, ProductionOrder::where('status', 'en_proceso')->count());
        $this->assertEqualsWithDelta(0.0, $this->lecheEnAlmacen(), 0.001);
    }

    public function test_el_aviso_dice_el_id_y_el_contenido_de_cada_lote(): void
    {
        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.lotes.store'), [
                'items' => [['product_id' => $this->queso->id, 'planned_quantity' => 2]],
                'iniciar_ahora' => '1',
            ])
            ->assertSessionHas('success', function (string $aviso) {
                $orden = ProductionOrder::firstOrFail();

                return str_contains($aviso, "#{$orden->id}")
                    && str_contains($aviso, $orden->batch_number)
                    && str_contains($aviso, $this->queso->name);
            });
    }

    public function test_los_renglones_a_medio_llenar_se_descartan(): void
    {
        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.lotes.store'), [
                'items' => [
                    ['product_id' => $this->queso->id, 'planned_quantity' => 1],
                    ['product_id' => '', 'planned_quantity' => ''],
                ],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, ProductionOrder::count());
    }

    public function test_el_admin_ve_el_reporte_pero_no_mueve_los_lotes(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $orden = $this->planificarUno();

        $this->actingAs($admin)
            ->get(route('produccion.lotes.index'))
            ->assertOk()
            ->assertSee($orden->batch_number)
            // Ni el botón de alta ni el de iniciar: solo el reporte.
            ->assertDontSee('data-nuevo-lote', false)
            ->assertDontSee(route('produccion.lotes.iniciar', $orden), false);

        $this->actingAs($admin)
            ->post(route('produccion.lotes.store'), [
                'items' => [['product_id' => $this->queso->id, 'planned_quantity' => 1]],
            ])
            ->assertRedirect(route('produccion.lotes.index'))
            ->assertSessionHasErrors('general');

        $this->actingAs($admin)
            ->post(route('produccion.lotes.iniciar', $orden))
            ->assertSessionHasErrors('general');

        $this->assertSame('planificada', $orden->fresh()->status);
    }

    public function test_el_jefe_de_produccion_si_mueve_los_lotes(): void
    {
        $orden = $this->planificarUno();

        $this->actingAs($this->jefePlanta)
            ->get(route('produccion.lotes.index'))
            ->assertOk()
            ->assertSee('data-nuevo-lote', false);

        $this->actingAs($this->jefePlanta)
            ->post(route('produccion.lotes.iniciar', $orden))
            ->assertSessionHasNoErrors();

        $this->assertSame('en_proceso', $orden->fresh()->status);
    }

    private function planificarUno(): ProductionOrder
    {
        $this->actingAs($this->jefePlanta)->post(route('produccion.lotes.store'), [
            'items' => [['product_id' => $this->queso->id, 'planned_quantity' => 1]],
        ]);

        return ProductionOrder::firstOrFail();
    }

    private function lecheEnAlmacen(): float
    {
        return InventoryStock::getStock(config('huata.codigos.leche'));
    }
}
