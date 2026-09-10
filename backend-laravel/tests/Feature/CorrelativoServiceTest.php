<?php

namespace Tests\Feature;

use App\Models\Correlativo;
use App\Models\Dispositivo;
use App\Models\Usuario;
use App\Services\Facturacion\CorrelativoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Requisito técnico 6: correlativo con reserva de rangos por dispositivo,
 * sin huecos ni repeticiones.
 */
class CorrelativoServiceTest extends TestCase
{
    use RefreshDatabase;

    private function dispositivo(): Dispositivo
    {
        $u = Usuario::factory()->create();

        return Dispositivo::create([
            'usuario_id' => $u->id,
            'identificador' => 'dev-'.uniqid(),
            'plataforma' => 'android',
        ]);
    }

    public function test_rangos_consecutivos_sin_huecos(): void
    {
        $svc = app(CorrelativoService::class);
        $correlativo = Correlativo::create(['tipo_comprobante' => 'boleta', 'serie' => 'B001']);

        $r1 = $svc->reservarRango($correlativo, $this->dispositivo(), 50);
        $r2 = $svc->reservarRango($correlativo, $this->dispositivo(), 50);

        $this->assertSame(1, $r1->numero_desde);
        $this->assertSame(50, $r1->numero_hasta);
        $this->assertSame(51, $r2->numero_desde); // contiguo, sin hueco
        $this->assertSame(100, $r2->numero_hasta);
    }

    public function test_emision_agota_el_rango_y_no_repite(): void
    {
        $svc = app(CorrelativoService::class);
        $correlativo = Correlativo::create(['tipo_comprobante' => 'boleta', 'serie' => 'B002']);
        $rango = $svc->reservarRango($correlativo, $this->dispositivo(), 3);

        $emitidos = [$svc->emitirNumero($rango), $svc->emitirNumero($rango), $svc->emitirNumero($rango)];

        $this->assertSame([1, 2, 3], $emitidos);
        $this->assertSame($emitidos, array_unique($emitidos));
        $this->assertTrue($rango->fresh()->agotado);

        $this->expectExceptionMessage('agotado');
        $svc->emitirNumero($rango->fresh());
    }
}
