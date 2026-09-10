<?php

namespace Tests\Feature;

use App\Enums\DictamenCalidad;
use App\Enums\EstadoProductor;
use App\Models\ControlCalidad;
use App\Models\PrecioCompraLeche;
use App\Models\Productor;
use App\Models\Ruta;
use App\Models\Usuario;
use App\Models\Zona;
use App\Services\Calidad\EvaluacionCalidadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * RN-05 (agua) y RN-06 (acidez). El dictamen se persiste y no se recalcula.
 */
class EvaluacionCalidadServiceTest extends TestCase
{
    use RefreshDatabase;

    private function productor(): Productor
    {
        $ruta = Ruta::create(['nombre' => 'R'.uniqid()]);
        $zona = Zona::create(['nombre' => 'Z'.uniqid(), 'ruta_id' => $ruta->id]);

        return Productor::create([
            'codigo_padron' => 'P'.uniqid(),
            'nombres' => 'Test', 'apellidos' => 'Productor',
            'dni' => (string) random_int(70000000, 79999999),
            'zona_id' => $zona->id, 'estado' => EstadoProductor::ACTIVO->value,
            'fecha_ingreso' => now()->toDateString(),
        ]);
    }

    private function control(Productor $p, array $mediciones): ControlCalidad
    {
        $supervisor = Usuario::factory()->create();

        return ControlCalidad::create(array_merge([
            'productor_id' => $p->id,
            'supervisor_id' => $supervisor->id,
            'tomado_en' => now(),
            'tipo' => 'inopinado',
            'dictamen' => DictamenCalidad::APROBADO->value, // provisional
        ], $mediciones));
    }

    public function test_agua_bajo_5_primera_vez_es_advertencia_con_descuento(): void
    {
        $p = $this->productor();
        $control = $this->control($p, ['agua_anadida_porcentaje' => 3.0]);

        $evaluado = app(EvaluacionCalidadService::class)->evaluar($control);

        $this->assertEquals(DictamenCalidad::ADVERTENCIA_AGUA, $evaluado->dictamen);
        $this->assertNotNull($evaluado->sancion);
        $this->assertEquals('advertencia_descuento', $evaluado->sancion->tipo->value);
        $this->assertEquals(EstadoProductor::ACTIVO, $p->fresh()->estado);
    }

    public function test_agua_bajo_5_reincidente_descuenta_y_retira(): void
    {
        $p = $this->productor();
        app(EvaluacionCalidadService::class)->evaluar($this->control($p, ['agua_anadida_porcentaje' => 2.0]));
        $segundo = app(EvaluacionCalidadService::class)->evaluar($this->control($p, ['agua_anadida_porcentaje' => 4.0]));

        $this->assertEquals(DictamenCalidad::DESCUENTO_RETIRO_AGUA, $segundo->dictamen);
        $this->assertEquals(EstadoProductor::RETIRADO, $p->fresh()->estado);
    }

    public function test_agua_5_o_mas_expulsa_y_degrada_tarifa(): void
    {
        PrecioCompraLeche::create([
            'precio_litro' => 1.70, 'precio_litro_minimo' => 0.65,
            'vigente_desde' => now()->subMonth()->toDateString(),
        ]);
        $p = $this->productor();
        $evaluado = app(EvaluacionCalidadService::class)->evaluar($this->control($p, ['agua_anadida_porcentaje' => 6.0]));

        $this->assertEquals(DictamenCalidad::EXPULSION_AGUA, $evaluado->dictamen);
        $this->assertTrue($evaluado->rechaza_lote);
        $this->assertEquals(EstadoProductor::EXPULSADO, $p->fresh()->estado);
        $this->assertEquals(0.65, (float) $evaluado->sancion->tarifa_degradada_litro);
    }

    public function test_ph_bajo_umbral_rechaza_y_deriva_a_capacitacion_sin_expulsion(): void
    {
        $p = $this->productor();
        $evaluado = app(EvaluacionCalidadService::class)->evaluar($this->control($p, ['ph' => 6.2]));

        $this->assertEquals(DictamenCalidad::RECHAZADO_ACIDEZ, $evaluado->dictamen);
        $this->assertTrue($evaluado->rechaza_lote);
        $this->assertNotNull($evaluado->capacitacion);
        $this->assertEquals(EstadoProductor::ACTIVO, $p->fresh()->estado);
    }

    public function test_el_dictamen_no_se_recalcula_al_leer(): void
    {
        $p = $this->productor();
        $control = $this->control($p, ['agua_anadida_porcentaje' => 3.0]);
        app(EvaluacionCalidadService::class)->evaluar($control);

        // Cambia la política a posteriori: no debe afectar al control ya emitido.
        config(['milkflow.agua.umbral_expulsion_pct' => 2.0]);

        $this->assertEquals(DictamenCalidad::ADVERTENCIA_AGUA, $control->fresh()->dictamen);
    }
}
