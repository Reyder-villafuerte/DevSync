<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionPriceRule;
use App\Models\Supply;
use App\Models\SystemPrice;
use App\Models\User;
use App\Services\Acopio\TarifaAcopioService;
use App\Services\Pagos\LiquidacionService;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Lo que la planta le paga al productor salió del código.
 *
 * Era un `if` sobre el porcentaje de agua contra tres columnas. Ahora son
 * filas con su condición, que el administrador carga. El día que se acopie
 * huevo, se cargan las suyas y ya.
 */
class TarifaAcopioTest extends TestCase
{
    use RefreshDatabase;

    private TarifaAcopioService $tarifas;

    private Supply $leche;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->tarifas = app(TarifaAcopioService::class);
        $this->leche = Supply::where('item_code', 'MILK_RAW_LITERS')->firstOrFail();
        $this->admin = User::where('role', 'admin')->firstOrFail();
    }

    public function test_las_tarifas_de_la_leche_quedan_sembradas_como_reglas(): void
    {
        $reglas = $this->tarifas->reglasDe($this->leche);

        $this->assertCount(3, $reglas);
        $this->assertEquals(1.40, $this->tarifas->precioBase($this->leche));

        $grave = $reglas->firstWhere('penalty_type', 'grave_expulsion');
        $this->assertTrue($grave->applies_to_whole_cycle, 'La regla de Huata: el castigo cae sobre toda la semana.');
    }

    public function test_la_leche_conforme_se_paga_a_tarifa_base(): void
    {
        $resuelta = $this->tarifas->resolver($this->leche, ['water_addition_percentage' => 0]);

        $this->assertEquals(1.40, $resuelta['price']);
        $this->assertSame('ninguna', $resuelta['penalty_type']);
    }

    public function test_entre_varias_tarifas_que_aplican_gana_la_de_mayor_prioridad(): void
    {
        // 7% de agua cumple «>0» y «>5»: tiene que ganar la grave.
        $resuelta = $this->tarifas->resolver($this->leche, ['water_addition_percentage' => 7]);

        $this->assertEquals(0.90, $resuelta['price']);
        $this->assertSame('grave_expulsion', $resuelta['penalty_type']);
        $this->assertTrue($resuelta['whole_cycle']);

        // 3% solo cumple «>0».
        $leve = $this->tarifas->resolver($this->leche, ['water_addition_percentage' => 3]);
        $this->assertEquals(1.20, $leve['price']);
        $this->assertSame('leve_descuento', $leve['penalty_type']);
    }

    public function test_el_admin_carga_una_tarifa_nueva_sin_tocar_codigo(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.precios.tarifas.store'), [
                'supply_id' => $this->leche->id,
                'name' => 'Bonificación por grasa alta',
                'metric' => 'fat_percentage',
                'operator' => '>=',
                'threshold' => 3.5,
                'price_per_unit' => 1.60,
                'priority' => 5,
            ])
            ->assertSessionHas('success');

        $resuelta = $this->tarifas->resolver($this->leche, [
            'water_addition_percentage' => 0,
            'fat_percentage' => 3.8,
        ]);

        $this->assertEquals(1.60, $resuelta['price']);
    }

    public function test_una_tarifa_condicionada_necesita_comparador_y_valor(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('comparador válido');

        $this->tarifas->crearRegla($this->leche, [
            'name' => 'Incompleta',
            'metric' => 'fat_percentage',
            'price_per_unit' => 1.50,
        ]);
    }

    public function test_no_puede_haber_dos_tarifas_base_del_mismo_producto(): void
    {
        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('ya tiene su tarifa base');

        $this->tarifas->crearRegla($this->leche, [
            'name' => 'Otra base',
            'price_per_unit' => 2.00,
        ]);
    }

    public function test_la_tarifa_base_no_se_elimina(): void
    {
        $base = CollectionPriceRule::whereNull('metric')->firstOrFail();

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('no se elimina');

        $this->tarifas->eliminarRegla($base);
    }

    public function test_cambiar_una_tarifa_actualiza_la_columna_que_lee_el_movil(): void
    {
        $grave = CollectionPriceRule::where('penalty_type', 'grave_expulsion')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.precios.tarifas.update', $grave), [
                'name' => $grave->name,
                'metric' => $grave->metric,
                'operator' => $grave->operator,
                'threshold' => $grave->threshold,
                'price_per_unit' => 0.75,
                'penalty_type' => 'grave_expulsion',
                'priority' => $grave->priority,
                'is_active' => 1,
            ])
            ->assertSessionHas('success');

        $this->assertEquals(0.75, (float) SystemPrice::current()->price_milk_water_penalty_high);
    }

    public function test_la_pantalla_de_precios_muestra_las_tarifas_de_acopio(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.precios.index'))
            ->assertOk()
            ->assertSee('Tarifas de acopio')
            ->assertSee('Agua sobre 5%')
            ->assertSee('Nueva tarifa')
            // Las tres casillas fijas ya no están.
            ->assertDontSee('name="price_milk_base"', false);
    }

    public function test_la_liquidacion_usa_la_tarifa_base_que_cargo_el_admin(): void
    {
        $base = CollectionPriceRule::whereNull('metric')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.precios.tarifas.update', $base), [
                'name' => 'Tarifa base',
                'price_per_unit' => 1.55,
                'is_active' => 1,
            ])
            ->assertSessionHas('success');

        $productor = User::where('role', 'productor')->firstOrFail();
        $ciclo = app(LiquidacionService::class)
            ->calcularCiclo($productor);

        $this->assertEquals(1.55, (float) $ciclo['base_price']);
    }
}
