<?php

namespace Tests\Unit;

use App\Models\Producto;
use App\Models\SesionProduccion;
use App\Services\Produccion\RendimientoService;
use PHPUnit\Framework\TestCase;

/**
 * RN-08: meta estricta de 11 a 12 quesos por cada 100 litros procesados.
 */
class RendimientoServiceTest extends TestCase
{
    private function sesion(float $litros, int $unidades): SesionProduccion
    {
        $producto = new Producto(['rendimiento_min_por_100l' => 11, 'rendimiento_max_por_100l' => 12]);
        $sesion = new SesionProduccion(['litros_procesados' => $litros, 'unidades_producidas' => $unidades]);
        $sesion->setRelation('producto', $producto);

        return $sesion;
    }

    public function test_dentro_de_meta_cumple(): void
    {
        $r = (new RendimientoService)->calcular($this->sesion(1000, 115)); // 11.5 / 100 L
        $this->assertSame(11.5, $r['rendimiento']);
        $this->assertTrue($r['cumple']);
    }

    public function test_por_debajo_de_meta_no_cumple(): void
    {
        $r = (new RendimientoService)->calcular($this->sesion(1000, 104)); // 10.4
        $this->assertFalse($r['cumple']);
        $this->assertStringContainsString('DEBAJO', $r['detalle']);
    }

    public function test_por_encima_de_meta_no_cumple(): void
    {
        $r = (new RendimientoService)->calcular($this->sesion(1000, 130)); // 13.0
        $this->assertFalse($r['cumple']);
        $this->assertStringContainsString('ENCIMA', $r['detalle']);
    }
}
