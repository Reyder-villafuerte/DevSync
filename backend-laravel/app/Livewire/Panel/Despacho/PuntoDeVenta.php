<?php

namespace App\Livewire\Panel\Despacho;

use App\Exceptions\ReglaNegocioException;
use App\Models\Cliente;
use App\Models\PrecioVenta;
use App\Models\Producto;
use App\Models\Venta;
use App\Services\Facturacion\VentaService;
use App\Services\Stock\StockService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Punto de venta: producto + tipo de cliente + cantidad, con precio de la
 * tarifa vigente y total en vivo. Valida stock antes de confirmar y muestra el
 * disponible real. La venta la registra VentaService (correlativo + stock, en
 * transacción).
 */
class PuntoDeVenta extends Component
{
    #[Validate('required|exists:clientes,id')]
    public ?string $clienteId = null;

    #[Validate('required|exists:productos,id')]
    public ?string $productoId = null;

    #[Validate('required|numeric|min:0.01')]
    public $cantidad = 1;

    public ?string $ventaId = null;

    public ?string $comprobante = null;

    #[Computed]
    public function precio(): ?PrecioVenta
    {
        if (! $this->clienteId || ! $this->productoId) {
            return null;
        }

        try {
            return app(VentaService::class)->tarifaVigente(
                Producto::findOrFail($this->productoId),
                Cliente::findOrFail($this->clienteId),
            );
        } catch (ReglaNegocioException) {
            return null;
        }
    }

    #[Computed]
    public function total(): float
    {
        $precio = $this->precio();

        return $precio && is_numeric($this->cantidad)
            ? round((float) $this->cantidad * (float) $precio->precio, 2)
            : 0.0;
    }

    #[Computed]
    public function disponible(): ?float
    {
        return $this->productoId
            ? app(StockService::class)->cantidadActual($this->productoId)
            : null;
    }

    public function confirmar(VentaService $servicio): void
    {
        $this->authorize('create', Venta::class);
        $this->validate();

        $disponible = $this->disponible();
        if ($disponible !== null && (float) $this->cantidad > $disponible) {
            $this->addError('cantidad', "Stock insuficiente: sólo hay {$disponible} disponibles.");

            return;
        }

        if (! $this->precio()) {
            $this->addError('productoId', 'No hay tarifa de venta vigente para ese producto y tipo de cliente.');

            return;
        }

        try {
            $venta = $servicio->registrar(
                Cliente::findOrFail($this->clienteId),
                Producto::findOrFail($this->productoId),
                (float) $this->cantidad,
                auth()->user(),
            );
        } catch (ReglaNegocioException $e) {
            $this->addError('cantidad', $e->getMessage());

            return;
        }

        $this->ventaId = $venta->id;
        $this->comprobante = $venta->comprobante();
        $this->reset(['clienteId', 'productoId', 'cantidad']);
        $this->cantidad = 1;
        $this->dispatch('venta-registrada');
        session()->flash('ok', "Venta {$this->comprobante} registrada y stock descontado.");
    }

    public function render()
    {
        return view('livewire.panel.despacho.punto-de-venta', [
            'clientes' => Cliente::query()->where('activo', true)->orderBy('nombre')->get(),
            'productos' => Producto::query()->where('activo', true)->orderBy('nombre')->get(),
        ]);
    }
}
