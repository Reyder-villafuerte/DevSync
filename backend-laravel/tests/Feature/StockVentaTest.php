<?php

namespace Tests\Feature;

use App\Enums\TipoMovimientoStock;
use App\Exceptions\ReglaNegocioException;
use App\Models\Producto;
use App\Models\Usuario;
use App\Services\Stock\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockVentaTest extends TestCase
{
    use RefreshDatabase;

    public function test_venta_sin_stock_se_rechaza_con_stock_insuficiente(): void
    {
        $producto = Producto::factory()->create();
        $usuario = Usuario::factory()->create();
        $stock = app(StockService::class);

        try {
            $stock->registrarMovimiento($producto, TipoMovimientoStock::VENTA_EGRESO, 3, usuarioId: $usuario->id);
            $this->fail('Debió lanzar ReglaNegocioException.');
        } catch (ReglaNegocioException $e) {
            $this->assertSame('stock_insuficiente', $e->regla);
            $this->assertSame(0, \App\Models\MovimientoStock::count());
        }
    }

    public function test_venta_con_stock_suficiente_genera_el_egreso(): void
    {
        $producto = Producto::factory()->create();
        $usuario = Usuario::factory()->create();
        $stock = app(StockService::class);

        $stock->registrarMovimiento($producto, TipoMovimientoStock::PRODUCCION_INGRESO, 10, usuarioId: $usuario->id);
        $stock->registrarMovimiento($producto, TipoMovimientoStock::VENTA_EGRESO, 4, usuarioId: $usuario->id);

        $this->assertEquals(6.0, $stock->cantidadActual($producto->id));
    }
}
