<?php

namespace Tests\Feature\Panel;

use App\Enums\RolUsuario;
use App\Livewire\Panel\Recepcion\RecepcionDia;
use App\Models\Acopiador;
use App\Models\Conciliacion;
use App\Models\RegistroAcopio;
use App\Models\RutaAcopio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Recepción del día: la conciliación marca alerta cuando la diferencia
 * volumétrica supera la tolerancia (1%), y no la marca cuando está dentro.
 */
class RecepcionConciliacionTest extends TestCase
{
    use CreaEntidadesPanel;
    use RefreshDatabase;

    private function jornadaConLitros(float $litrosAcopiador): RutaAcopio
    {
        $zona = $this->zona();
        $jefe = $this->usuario(RolUsuario::ACOPIADOR);
        $acopiador = Acopiador::create([
            'usuario_id' => $jefe->id,
            'ruta_id' => $zona->ruta_id,
            'vigente_desde' => now()->subYear()->toDateString(),
        ]);

        $jornada = RutaAcopio::create([
            'acopiador_id' => $acopiador->id,
            'ruta_id' => $zona->ruta_id,
            'fecha' => now()->toDateString(),
            'hora_inicio' => now()->subHours(3),
            'estado' => 'cerrada',
        ]);

        RegistroAcopio::create([
            'ruta_acopio_id' => $jornada->id,
            'productor_id' => $this->productor($zona)->id,
            'litros' => $litrosAcopiador,
            'hora_registro' => now()->subHours(2),
        ]);

        return $jornada;
    }

    public function test_diferencia_sobre_tolerancia_genera_alerta(): void
    {
        $jornada = $this->jornadaConLitros(100);
        $jefe = $this->usuario(RolUsuario::JEFE_PRODUCCION);

        Livewire::actingAs($jefe)
            ->test(RecepcionDia::class)
            ->set('rutaAcopioId', $jornada->id)
            ->set('litrosCaudalimetro', 102) // +2% => supera el 1%
            ->call('conciliar')
            ->assertHasNoErrors();

        $conciliacion = Conciliacion::firstWhere('ruta_acopio_id', $jornada->id);
        $this->assertNotNull($conciliacion);
        $this->assertTrue($conciliacion->tiene_alerta);
        $this->assertEqualsWithDelta(2.0, (float) $conciliacion->diferencia_porcentaje, 0.01);
    }

    public function test_diferencia_dentro_de_tolerancia_no_genera_alerta(): void
    {
        $jornada = $this->jornadaConLitros(100);
        $jefe = $this->usuario(RolUsuario::JEFE_PRODUCCION);

        Livewire::actingAs($jefe)
            ->test(RecepcionDia::class)
            ->set('rutaAcopioId', $jornada->id)
            ->set('litrosCaudalimetro', 100.5) // +0.5%
            ->call('conciliar')
            ->assertHasNoErrors();

        $this->assertFalse(Conciliacion::firstWhere('ruta_acopio_id', $jornada->id)->tiene_alerta);
    }
}
