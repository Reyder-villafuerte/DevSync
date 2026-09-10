<?php

namespace App\Livewire\Panel\Admin;

use App\Enums\TipoCliente;
use App\Exceptions\ReglaNegocioException;
use App\Models\PrecioCompraLeche;
use App\Models\PrecioVenta;
use App\Models\Producto;
use App\Services\Tarifas\TarifaService;
use Livewire\Component;

/**
 * Pestaña Tarifas: precio de compra de leche y lista de precios de venta por
 * producto × tipo de cliente. Guardar cierra la vigencia anterior y abre una
 * nueva (TarifaService); el histórico no se sobrescribe.
 */
class Tarifas extends Component
{
    public $precioLitro;

    public $precioLitroMinimo;

    /** [productoId => [tipoCliente => precio]]. */
    public array $preciosVenta = [];

    public function mount(): void
    {
        $compra = PrecioCompraLeche::query()->whereNull('vigente_hasta')->orderByDesc('vigente_desde')->first();
        $this->precioLitro = $compra?->precio_litro;
        $this->precioLitroMinimo = $compra?->precio_litro_minimo;

        foreach (Producto::query()->where('activo', true)->get() as $producto) {
            foreach (TipoCliente::cases() as $tipo) {
                $vigente = PrecioVenta::query()
                    ->where('producto_id', $producto->id)
                    ->where('tipo_cliente', $tipo->value)
                    ->whereNull('vigente_hasta')
                    ->orderByDesc('vigente_desde')
                    ->first();
                $this->preciosVenta[$producto->id][$tipo->value] = $vigente?->precio;
            }
        }
    }

    public function guardarCompra(TarifaService $servicio): void
    {
        $this->authorize('panel-administracion');
        $this->validate([
            'precioLitro' => ['required', 'numeric', 'gt:0'],
            'precioLitroMinimo' => ['required', 'numeric', 'gt:0'],
        ], [], ['precioLitro' => 'precio por litro', 'precioLitroMinimo' => 'tarifa mínima']);

        try {
            $servicio->fijarPrecioCompraLeche((float) $this->precioLitro, (float) $this->precioLitroMinimo);
        } catch (ReglaNegocioException $e) {
            $this->addError('precioLitro', $e->getMessage());

            return;
        }

        session()->flash('ok', 'Precio de compra de leche actualizado; la vigencia anterior quedó cerrada.');
    }

    public function guardarVenta(TarifaService $servicio, string $productoId): void
    {
        $this->authorize('panel-administracion');
        $producto = Producto::findOrFail($productoId);

        foreach (TipoCliente::cases() as $tipo) {
            $valor = $this->preciosVenta[$productoId][$tipo->value] ?? null;
            if ($valor === null || $valor === '') {
                continue;
            }

            try {
                $servicio->fijarPrecioVenta($producto, $tipo, (float) $valor);
            } catch (ReglaNegocioException $e) {
                $this->addError("preciosVenta.{$productoId}.{$tipo->value}", $e->getMessage());

                return;
            }
        }

        session()->flash('ok', "Precios de venta de {$producto->nombre} actualizados.");
    }

    public function render()
    {
        return view('livewire.panel.admin.tarifas', [
            'productos' => Producto::query()->where('activo', true)->orderBy('nombre')->get(),
            'tipos' => TipoCliente::cases(),
            'historicoCompra' => PrecioCompraLeche::query()->orderByDesc('vigente_desde')->take(8)->get(),
        ]);
    }
}
