<?php

namespace Tests\Feature\Panel;

use App\Enums\EstadoRecepcion;
use App\Enums\RolUsuario;
use App\Livewire\Panel\Recepcion\VerificacionEntregas;
use App\Models\Acopiador;
use App\Models\RegistroAcopio;
use App\Models\RutaAcopio;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Verificación de entregas: el jefe de producción confirma, entrega por
 * entrega, cuántos litros recibió; si el medidor de tina da menos, la entrega
 * queda "faltante" con el detalle de litros que faltaron.
 */
class VerificacionEntregasTest extends TestCase
{
    use CreaEntidadesPanel;
    use RefreshDatabase;

    /** @return array{RutaAcopio, RegistroAcopio, \App\Models\Productor} */
    private function rutaCerradaConEntrega(float $litrosDeclarados = 40): array
    {
        $zona = $this->zona();
        $acopiador = Acopiador::create([
            'usuario_id' => $this->usuario(RolUsuario::ACOPIADOR)->id,
            'ruta_id' => $zona->ruta_id,
            'vigente_desde' => now()->subYear()->toDateString(),
        ]);

        $ruta = RutaAcopio::create([
            'acopiador_id' => $acopiador->id,
            'ruta_id' => $zona->ruta_id,
            'fecha' => now()->toDateString(),
            'hora_inicio' => now()->subHours(3),
            'estado' => 'cerrada',
        ]);

        $productor = $this->productor($zona);
        $registro = RegistroAcopio::create([
            'ruta_acopio_id' => $ruta->id,
            'productor_id' => $productor->id,
            'litros' => $litrosDeclarados,
            'hora_registro' => now()->subHours(2),
        ]);

        return [$ruta, $registro, $productor];
    }

    public function test_entrega_nace_pendiente_de_recepcion(): void
    {
        [, $registro] = $this->rutaCerradaConEntrega();

        $this->assertSame(EstadoRecepcion::PENDIENTE, $registro->fresh()->estado_recepcion);
    }

    public function test_marcar_conforme_deja_la_entrega_conforme_sin_faltante(): void
    {
        [$ruta, $registro] = $this->rutaCerradaConEntrega(40);
        $jefe = $this->usuario(RolUsuario::JEFE_PRODUCCION);

        Livewire::actingAs($jefe)
            ->test(VerificacionEntregas::class)
            ->set('rutaAcopioId', $ruta->id)
            ->call('marcarConforme', $registro->id)
            ->assertHasNoErrors();

        $registro->refresh();
        $this->assertSame(EstadoRecepcion::CONFORME, $registro->estado_recepcion);
        $this->assertEquals(40.0, (float) $registro->litros_recibidos);
        $this->assertEquals(0.0, (float) $registro->litros_faltantes);
        $this->assertSame($jefe->id, $registro->recepcion_confirmada_por);
    }

    public function test_recibir_menos_marca_faltante_con_litros_que_faltaron(): void
    {
        [$ruta, $registro] = $this->rutaCerradaConEntrega(40);
        $jefe = $this->usuario(RolUsuario::JEFE_PRODUCCION);

        Livewire::actingAs($jefe)
            ->test(VerificacionEntregas::class)
            ->set('rutaAcopioId', $ruta->id)
            ->set("recibidos.{$registro->id}", '36')
            ->call('guardarRecibido', $registro->id)
            ->assertHasNoErrors();

        $registro->refresh();
        $this->assertSame(EstadoRecepcion::FALTANTE, $registro->estado_recepcion);
        $this->assertEquals(36.0, (float) $registro->litros_recibidos);
        $this->assertEquals(4.0, (float) $registro->litros_faltantes);
    }

    public function test_marcar_todo_conforme_verifica_las_entregas_pendientes(): void
    {
        [$ruta, $registro] = $this->rutaCerradaConEntrega(40);
        $jefe = $this->usuario(RolUsuario::JEFE_PRODUCCION);

        Livewire::actingAs($jefe)
            ->test(VerificacionEntregas::class)
            ->set('rutaAcopioId', $ruta->id)
            ->call('marcarTodoConforme')
            ->assertHasNoErrors();

        $this->assertSame(EstadoRecepcion::CONFORME, $registro->fresh()->estado_recepcion);
    }

    public function test_un_acopiador_no_puede_verificar_entregas(): void
    {
        [$ruta, $registro] = $this->rutaCerradaConEntrega();

        Livewire::actingAs($this->usuario(RolUsuario::ACOPIADOR))
            ->test(VerificacionEntregas::class)
            ->set('rutaAcopioId', $ruta->id)
            ->call('marcarConforme', $registro->id)
            ->assertForbidden();

        $this->assertSame(EstadoRecepcion::PENDIENTE, $registro->fresh()->estado_recepcion);
    }
}
