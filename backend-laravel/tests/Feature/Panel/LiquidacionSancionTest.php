<?php

namespace Tests\Feature\Panel;

use App\Enums\DictamenCalidad;
use App\Enums\RolUsuario;
use App\Models\Acopiador;
use App\Models\ControlCalidad;
use App\Models\PrecioCompraLeche;
use App\Models\RegistroAcopio;
use App\Models\RutaAcopio;
use App\Models\Sancion;
use App\Models\SemanaPago;
use App\Services\Liquidacion\LiquidacionService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Liquidación del viernes con una sanción RN-05 (<5%): el descuento se aplica
 * sobre el bruto y el neto refleja bruto − descuentos.
 */
class LiquidacionSancionTest extends TestCase
{
    use CreaEntidadesPanel;
    use RefreshDatabase;

    public function test_liquidacion_aplica_el_descuento_por_sancion(): void
    {
        PrecioCompraLeche::create([
            'precio_litro' => 1.70,
            'precio_litro_minimo' => 0.65,
            'vigente_desde' => now()->subMonth()->toDateString(),
        ]);

        $zona = $this->zona();
        $productor = $this->productor($zona);

        $acopiadorUsuario = $this->usuario(RolUsuario::ACOPIADOR);
        $acopiador = Acopiador::create([
            'usuario_id' => $acopiadorUsuario->id,
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
            'productor_id' => $productor->id,
            'litros' => 100,
            'hora_registro' => now()->subHour(),
        ]);

        $control = ControlCalidad::create([
            'productor_id' => $productor->id,
            'supervisor_id' => $this->usuario(RolUsuario::SUPERVISOR_CALIDAD)->id,
            'tomado_en' => now()->subDay(),
            'tipo' => 'inopinado',
            'agua_anadida_porcentaje' => 3.0,
            'dictamen' => DictamenCalidad::ADVERTENCIA_AGUA->value,
        ]);
        Sancion::create([
            'productor_id' => $productor->id,
            'control_calidad_id' => $control->id,
            'tipo' => 'advertencia_descuento',
            'porcentaje_descuento' => 0.15,
            'estado' => 'vigente',
            'aplicada_en_liquidacion' => false,
        ]);

        $rango = SemanaPago::paraFecha(CarbonImmutable::now());
        $semana = SemanaPago::firstOrCreate(
            ['fecha_inicio' => $rango['fecha_inicio'], 'fecha_fin' => $rango['fecha_fin']],
            ['fecha_liquidacion' => $rango['fecha_liquidacion'], 'estado' => 'abierta'],
        );

        $liquidaciones = app(LiquidacionService::class)->generarSemana($semana);

        $liquidacion = $liquidaciones->firstWhere('productor_id', $productor->id);
        $this->assertNotNull($liquidacion);
        $this->assertEqualsWithDelta(170.00, (float) $liquidacion->monto_bruto, 0.01);
        $this->assertEqualsWithDelta(25.50, (float) $liquidacion->total_descuentos, 0.01);
        $this->assertEqualsWithDelta(144.50, (float) $liquidacion->monto_neto, 0.01);
        $this->assertSame(
            round((float) $liquidacion->monto_bruto - (float) $liquidacion->total_descuentos, 2),
            round((float) $liquidacion->monto_neto, 2),
        );
        $this->assertSame('aplicada', Sancion::first()->fresh()->estado);
    }
}
