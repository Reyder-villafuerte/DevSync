<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Acopio\JornadaOperativa;
use Database\Seeders\MilkFlowHuataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Lo que el acopiador registra en el móvil debe notarse en la web:
 * la huella de datos cambia y las pantallas abiertas se recargan solas.
 */
class TiempoRealTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MilkFlowHuataSeeder::class);
    }

    public function test_la_huella_cambia_cuando_el_movil_registra_una_entrega(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $acopiador = User::where('role', 'acopiador')->firstOrFail();
        $productor = User::where('role', 'productor')->firstOrFail();

        $antes = $this->actingAs($admin)->getJson(route('tiempo-real.version'))->assertOk()->json('version');
        $igual = $this->actingAs($admin)->getJson(route('tiempo-real.version'))->json('version');
        $this->assertSame($antes, $igual, 'Sin cambios, la huella no debe moverse');

        $this->actingAs($acopiador, 'sanctum')->postJson('/api/sync/push', [
            'device_id' => 'telefono-ruta',
            'operaciones' => [[
                'client_uuid' => (string) Str::uuid(),
                'comando' => 'registrar_entrega',
                'payload' => [
                    'ruta_client_uuid' => (string) Str::uuid(),
                    'fecha' => app(JornadaOperativa::class)->fecha(),
                    'producer_id' => $productor->id,
                    'liters' => 20,
                    'collected_at' => '04:50:00',
                ],
            ]],
        ])->assertOk()->assertJsonPath('resultados.0.estado', 'aplicada');

        $despues = $this->actingAs($admin)->getJson(route('tiempo-real.version'))->json('version');
        $this->assertNotSame($antes, $despues);
    }

    public function test_la_huella_exige_sesion(): void
    {
        $this->getJson(route('tiempo-real.version'))->assertUnauthorized();
    }
}
