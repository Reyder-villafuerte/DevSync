<?php

namespace Tests\Feature;

use App\Models\CollectionRoute;
use App\Models\User;
use App\Models\Zone;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El reparto del día se ve y se hace desde la propia pantalla de Zonas:
 * la tabla dice quién cubre cada sector y el botón abre el formulario.
 */
class AsignacionDeZonaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->admin = User::where('role', 'admin')->firstOrFail();
    }

    public function test_la_pantalla_de_zonas_muestra_la_tabla_de_asignacion_y_su_boton(): void
    {
        $this->actingAs($this->admin)
            ->get(route('zonas.index'))
            ->assertOk()
            ->assertSee('Asignación de zonas de hoy')
            ->assertSee('Asignar zona')
            ->assertSee('Acopiador asignado')
            ->assertViewHas('collectors', fn ($collectors) => $collectors->isNotEmpty());
    }

    public function test_una_zona_sin_acopiador_aparece_como_sin_asignar(): void
    {
        CollectionRoute::query()->delete();

        $this->actingAs($this->admin)
            ->get(route('zonas.index'))
            ->assertOk()
            ->assertSee('Sin asignar');
    }

    public function test_asignar_una_zona_desde_la_pantalla_deja_la_ruta_del_dia(): void
    {
        CollectionRoute::query()->delete();

        $zona = Zone::orderBy('id')->firstOrFail();
        $acopiador = User::where('role', 'acopiador')->orderBy('id')->firstOrFail();
        $hoy = now()->format('Y-m-d');

        $this->actingAs($this->admin)
            ->from(route('zonas.index'))
            ->post(route('acopio.assign'), [
                'date' => $hoy,
                'zone_id' => $zona->id,
                'collector_id' => $acopiador->id,
                'start_time' => '04:30',
            ])
            ->assertRedirect(route('zonas.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('collection_routes', [
            'date' => $hoy,
            'zone_id' => $zona->id,
            'collector_id' => $acopiador->id,
        ]);

        // Y el acopiador ya figura en la tabla de la pantalla.
        $this->actingAs($this->admin)
            ->get(route('zonas.index'))
            ->assertOk()
            ->assertSee($acopiador->name);
    }

    public function test_un_acopiador_no_puede_quedar_en_dos_zonas_el_mismo_dia(): void
    {
        CollectionRoute::query()->delete();

        $zonas = Zone::orderBy('id')->take(2)->get();
        $acopiador = User::where('role', 'acopiador')->orderBy('id')->firstOrFail();
        $hoy = now()->format('Y-m-d');

        $this->actingAs($this->admin)->post(route('acopio.assign'), [
            'date' => $hoy,
            'zone_id' => $zonas[0]->id,
            'collector_id' => $acopiador->id,
            'start_time' => '04:30',
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->from(route('zonas.index'))
            ->post(route('acopio.assign'), [
                'date' => $hoy,
                'zone_id' => $zonas[1]->id,
                'collector_id' => $acopiador->id,
                'start_time' => '04:30',
            ])
            ->assertSessionHasErrors('collector_id');

        $this->assertSame(1, CollectionRoute::where('date', $hoy)->count());
    }

    public function test_las_solicitudes_de_rotacion_viven_en_su_propia_pantalla(): void
    {
        // La tarjeta salió de /zonas; la bandeja dedicada sigue siendo la de siempre.
        $this->actingAs($this->admin)
            ->get(route('zonas.index'))
            ->assertOk()
            ->assertDontSee('Solicitudes de Rotación de Zona Pendientes');

        $this->actingAs($this->admin)
            ->get(route('zonas.solicitudes'))
            ->assertOk();
    }

    public function test_el_acopiador_conserva_su_acceso_a_la_jornada_de_acopio(): void
    {
        $acopiador = User::where('role', 'acopiador')->firstOrFail();

        // Su pantalla de trabajo diaria sigue en el menú aunque la jefatura ya no la vea.
        $this->actingAs($acopiador)
            ->get(route('acopio.index'))
            ->assertOk()
            ->assertSee('Acopio 4:30 AM');

        $this->actingAs($this->admin)
            ->get(route('zonas.index'))
            ->assertOk()
            ->assertDontSee('Acopio 4:30 AM');
    }

    public function test_el_acopiador_no_ve_la_tabla_de_asignacion(): void
    {
        $acopiador = User::where('role', 'acopiador')->firstOrFail();

        $this->actingAs($acopiador)
            ->get(route('zonas.index'))
            ->assertOk()
            ->assertDontSee('Asignación de zonas de hoy');
    }
}
