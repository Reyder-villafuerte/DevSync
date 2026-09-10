<?php

namespace Tests\Feature\Panel;

use App\Enums\RolUsuario;
use App\Enums\TipoMovimientoStock;
use App\Livewire\Panel\Despacho\PuntoDeVenta;
use App\Models\MovimientoStock;
use App\Models\Venta;
use App\Services\Stock\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Punto de venta: la venta con stock insuficiente se rechaza mostrando el
 * disponible real, y no crea Venta ni movimiento de stock; con stock suficiente
 * emite el comprobante y descuenta.
 */
class PuntoDeVentaStockTest extends TestCase
{
    use CreaEntidadesPanel;
    use RefreshDatabase;

    public function test_venta_sin_stock_se_rechaza_y_no_persiste_nada(): void
    {
        $this->mostradorWeb();
        $producto = $this->productoConTarifa('publico', 18.0);
        $cliente = $this->clientePublico();
        $vendedor = $this->usuario(RolUsuario::DESPACHO_VENTAS);

        Livewire::actingAs($vendedor)
            ->test(PuntoDeVenta::class)
            ->set('clienteId', $cliente->id)
            ->set('productoId', $producto->id)
            ->set('cantidad', 5)
            ->call('confirmar')
            ->assertHasErrors('cantidad');

        $this->assertSame(0, Venta::count());
        $this->assertSame(0, MovimientoStock::count());
    }

    public function test_venta_con_stock_emite_comprobante_y_descuenta(): void
    {
        $this->mostradorWeb();
        $producto = $this->productoConTarifa('publico', 18.0);
        $cliente = $this->clientePublico();
        $vendedor = $this->usuario(RolUsuario::DESPACHO_VENTAS);

        app(StockService::class)->registrarMovimiento(
            $producto, TipoMovimientoStock::PRODUCCION_INGRESO, 10, usuarioId: $vendedor->id,
        );

        Livewire::actingAs($vendedor)
            ->test(PuntoDeVenta::class)
            ->set('clienteId', $cliente->id)
            ->set('productoId', $producto->id)
            ->set('cantidad', 4)
            ->call('confirmar')
            ->assertHasNoErrors()
            ->assertSet('comprobante', fn ($v) => is_string($v) && str_starts_with($v, 'B001-'));

        $this->assertSame(1, Venta::count());
        $venta = Venta::first();
        $this->assertSame(72.0, (float) $venta->total);
        $this->assertNotNull($venta->rango_correlativo_id);
        $this->assertEqualsWithDelta(6.0, app(StockService::class)->cantidadActual($producto->id), 0.001);
    }
}
