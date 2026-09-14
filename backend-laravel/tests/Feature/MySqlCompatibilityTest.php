<?php

namespace Tests\Feature;

use App\Enums\TipoCliente;
use App\Enums\TipoMovimientoStock;
use App\Models\Correlativo;
use App\Models\Dispositivo;
use App\Models\PrecioCompraLeche;
use App\Models\PrecioVenta;
use App\Models\Producto;
use App\Models\Productor;
use App\Models\RangoCorrelativo;
use App\Models\Ruta;
use App\Models\StockActual;
use App\Models\Usuario;
use App\Models\Zona;
use App\Services\Asamblea\AsambleaService;
use App\Services\Stock\StockService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MySqlCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_vista_stock_actual_refleja_movimientos_sin_refresco(): void
    {
        $producto = Producto::factory()->create();
        $usuario = Usuario::factory()->create();

        app(StockService::class)->registrarMovimiento(
            $producto,
            TipoMovimientoStock::PRODUCCION_INGRESO,
            8.5,
            usuarioId: $usuario->id,
        );

        $this->assertEquals(8.5, (float) StockActual::findOrFail($producto->id)->cantidad_actual);
    }

    public function test_mysql_impide_solapar_precios_de_venta(): void
    {
        $producto = Producto::factory()->create();
        PrecioVenta::create([
            'producto_id' => $producto->id,
            'tipo_cliente' => TipoCliente::SOCIO->value,
            'precio' => 10,
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => '2026-01-31',
        ]);

        $this->expectException(QueryException::class);
        PrecioVenta::create([
            'producto_id' => $producto->id,
            'tipo_cliente' => TipoCliente::SOCIO->value,
            'precio' => 11,
            'vigente_desde' => '2026-01-31',
            'vigente_hasta' => null,
        ]);
    }

    public function test_mysql_impide_solapar_precios_de_compra(): void
    {
        PrecioCompraLeche::create([
            'precio_litro' => 1.7,
            'precio_litro_minimo' => 0.7,
            'vigente_desde' => '2026-01-01',
            'vigente_hasta' => '2026-01-31',
        ]);

        $this->expectException(QueryException::class);
        PrecioCompraLeche::create([
            'precio_litro' => 1.8,
            'precio_litro_minimo' => 0.7,
            'vigente_desde' => '2026-01-15',
            'vigente_hasta' => null,
        ]);
    }

    public function test_mysql_impide_solapar_rangos_de_correlativo(): void
    {
        $usuario = Usuario::factory()->create();
        $dispositivo = Dispositivo::create([
            'usuario_id' => $usuario->id,
            'identificador' => 'mysql-compatibilidad',
        ]);
        $correlativo = Correlativo::create([
            'tipo_comprobante' => 'boleta',
            'serie' => 'B001',
        ]);
        RangoCorrelativo::create([
            'correlativo_id' => $correlativo->id,
            'dispositivo_id' => $dispositivo->id,
            'numero_desde' => 1,
            'numero_hasta' => 50,
            'numero_siguiente' => 1,
            'reservado_en' => now(),
        ]);

        $this->expectException(QueryException::class);
        RangoCorrelativo::create([
            'correlativo_id' => $correlativo->id,
            'dispositivo_id' => $dispositivo->id,
            'numero_desde' => 50,
            'numero_hasta' => 100,
            'numero_siguiente' => 50,
            'reservado_en' => now(),
        ]);
    }

    public function test_busqueda_de_padron_ignora_acentos_y_mayusculas(): void
    {
        $ruta = Ruta::create(['nombre' => 'Ruta prueba']);
        $zona = Zona::create(['nombre' => 'Zona prueba', 'ruta_id' => $ruta->id]);
        $productor = Productor::create([
            'codigo_padron' => 'P-MYSQL',
            'nombres' => 'José',
            'apellidos' => 'Núñez',
            'dni' => '70000001',
            'zona_id' => $zona->id,
            'estado' => 'activo',
            'fecha_ingreso' => '2026-01-01',
        ]);

        $resultado = app(AsambleaService::class)->buscarPadron('JOSE NUNEZ');

        $this->assertTrue($resultado->contains('id', $productor->id));
    }
}
