<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionRoute;
use App\Models\User;
use App\Services\Acopio\AcopioService;
use App\Services\Acopio\JornadaOperativa;
use App\Services\Planta\PlantaService;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * La ruta de acopio abre a las 4:30 y, una vez descargada en planta, no admite
 * un litro más.
 *
 * Son las dos reglas que sostienen el cuadre: si se pudiera anotar leche
 * después de que el caudalímetro midió, lo declarado en campo y lo medido en
 * planta dejarían de poder compararse.
 */
class RutaDeAcopioCierreTest extends TestCase
{
    use RefreshDatabase;

    private AcopioService $acopio;

    private User $acopiador;

    private int $zonaId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);

        $this->acopio = app(AcopioService::class);
        $this->acopiador = User::where('role', 'acopiador')->firstOrFail();

        $this->zonaId = User::where('role', 'productor')->whereNotNull('zone_id')
            ->selectRaw('zone_id, count(*) as cuantos')
            ->groupBy('zone_id')
            ->orderByDesc('cuantos')
            ->value('zone_id');

        $this->assertNotNull($this->zonaId, 'El padrón necesita productores con zona.');

        CollectionRoute::query()->delete();
    }

    public function test_la_ruta_del_dia_abre_a_las_cuatro_y_media_de_la_jornada(): void
    {
        $ruta = $this->acopio->rutaDelDia($this->acopiador);

        $this->assertNotNull($ruta);
        $this->assertSame('04:30:00', $ruta->start_time);
        $this->assertSame(app(JornadaOperativa::class)->fecha(), $ruta->date);
        $this->assertSame('asignada', $ruta->status);
        $this->assertEquals(0.0, (float) $ruta->total_collected_liters);
    }

    public function test_cada_jornada_abre_su_propia_ruta_y_no_reusa_la_de_ayer(): void
    {
        $hoy = app(JornadaOperativa::class)->fecha();

        $ayer = CollectionRoute::create([
            'date' => date('Y-m-d', strtotime($hoy.' -1 day')),
            'zone_id' => $this->zonaId,
            'collector_id' => $this->acopiador->id,
            'start_time' => '04:30:00',
            'status' => 'descargada_planta',
            'total_collected_liters' => 120,
        ]);

        $ruta = $this->acopio->rutaDelDia($this->acopiador);

        $this->assertNotSame($ayer->id, $ruta->id);
        $this->assertSame($hoy, $ruta->date);
    }

    public function test_la_ruta_de_otra_jornada_no_admite_litros(): void
    {
        $hoy = app(JornadaOperativa::class)->fecha();

        $ayer = CollectionRoute::create([
            'date' => date('Y-m-d', strtotime($hoy.' -1 day')),
            'zone_id' => $this->zonaId,
            'collector_id' => $this->acopiador->id,
            'start_time' => '04:30:00',
            'status' => 'en_ruta',
            'total_collected_liters' => 0,
        ]);

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('no corresponde a la jornada actual');

        $this->acopio->registrarEntrega($ayer, $this->productorDe($ayer)->id, 20.0);
    }

    public function test_una_ruta_cerrada_no_admite_un_litro_mas(): void
    {
        $ruta = $this->acopio->rutaDelDia($this->acopiador);
        $productores = $this->productoresDe($ruta);

        $this->acopio->registrarEntrega($ruta, $productores[0]->id, 30.0);
        $this->acopio->cerrarRuta($ruta);

        $litrosAlCerrar = (float) $ruta->fresh()->total_collected_liters;

        try {
            $this->acopio->registrarEntrega($ruta->fresh(), $productores[1]->id, 15.0);
            $this->fail('Se aceptaron litros en una ruta ya descargada en planta.');
        } catch (ReglaNegocioException $e) {
            $this->assertStringContainsString('no admite cambios', $e->getMessage());
        }

        $this->assertEquals($litrosAlCerrar, (float) $ruta->fresh()->total_collected_liters);
        $this->assertSame(1, $ruta->fresh()->records()->count());
    }

    public function test_una_ruta_verificada_tampoco_admite_litros(): void
    {
        $ruta = $this->acopio->rutaDelDia($this->acopiador);
        $productores = $this->productoresDe($ruta);

        $this->acopio->registrarEntrega($ruta, $productores[0]->id, 40.0);
        $this->acopio->cerrarRuta($ruta);

        app(PlantaService::class)->verificarRecepcion(
            $ruta->fresh(),
            User::where('role', 'jefe_produccion')->firstOrFail(),
            38.0,
            'verificado'
        );

        $this->expectException(ReglaNegocioException::class);
        $this->expectExceptionMessage('no admite cambios');

        $this->acopio->registrarEntrega($ruta->fresh(), $productores[1]->id, 10.0);
    }

    public function test_el_acopiador_tampoco_puede_anotar_desde_la_web_con_la_ruta_cerrada(): void
    {
        $ruta = $this->acopio->rutaDelDia($this->acopiador);
        $productores = $this->productoresDe($ruta);

        $this->actingAs($this->acopiador)
            ->post(route('acopio.discharge', $ruta))
            ->assertSessionHasNoErrors();

        $this->actingAs($this->acopiador)
            ->post(route('acopio.delivery', $ruta), [
                'producer_id' => $productores[0]->id,
                'liters' => 25.0,
            ])
            ->assertSessionHasErrors('liters');

        $this->assertSame(0, $ruta->fresh()->records()->count());
    }

    public function test_el_movil_tampoco_puede_subir_litros_a_una_ruta_cerrada(): void
    {
        $ruta = $this->acopio->rutaDelDia($this->acopiador);
        $productores = $this->productoresDe($ruta);

        $this->acopio->cerrarRuta($ruta);

        $respuesta = $this->actingAs($this->acopiador, 'sanctum')->postJson('/api/sync/push', [
            'operaciones' => [[
                'client_uuid' => (string) Str::uuid(),
                'comando' => 'registrar_entrega',
                'payload' => [
                    'ruta_id' => $ruta->id,
                    'producer_id' => $productores[0]->id,
                    'liters' => 18.0,
                ],
            ]],
        ]);

        $respuesta->assertOk();
        // Rechazo de negocio: el teléfono no debe reintentarlo nunca.
        $respuesta->assertJsonPath('resultados.0.estado', 'rechazada');
        $this->assertStringContainsString('no admite cambios', $respuesta->json('resultados.0.mensaje'));
        $this->assertSame(0, $ruta->fresh()->records()->count());
    }

    public function test_cerrar_una_ruta_ya_verificada_no_la_devuelve_a_pendiente(): void
    {
        $ruta = $this->acopio->rutaDelDia($this->acopiador);
        $this->acopio->registrarEntrega($ruta, $this->productoresDe($ruta)[0]->id, 50.0);
        $this->acopio->cerrarRuta($ruta);

        app(PlantaService::class)->verificarRecepcion(
            $ruta->fresh(),
            User::where('role', 'jefe_produccion')->firstOrFail(),
            48.0,
            'verificado'
        );

        $this->assertSame('verificada', $ruta->fresh()->status);

        // Volver a pulsar «descargar en planta» no puede deshacer el caudalímetro.
        try {
            $this->acopio->cerrarRuta($ruta->fresh());
        } catch (ReglaNegocioException $e) {
            $this->assertStringContainsString('ya', $e->getMessage());
        }

        $this->assertSame('verificada', $ruta->fresh()->status);
    }

    /** @return array<int, User> */
    private function productoresDe(CollectionRoute $ruta): array
    {
        $productores = User::where('role', 'productor')
            ->where('zone_id', $ruta->zone_id)
            ->orderBy('id')
            ->take(2)
            ->get()
            ->all();

        $this->assertCount(2, $productores, 'La zona necesita al menos dos productores para esta prueba.');

        return $productores;
    }

    private function productorDe(CollectionRoute $ruta): User
    {
        return User::where('role', 'productor')->where('zone_id', $ruta->zone_id)->firstOrFail();
    }
}
