<?php

namespace App\Livewire\Panel\Despacho;

use App\Models\StockActual;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Stock disponible por producto (derivado de movimientos_stock vía la vista
 * materializada stock_actual). Se refresca por polling y al recibir eventos de
 * producción/venta, para que un lote completado aparezca sin recargar.
 */
class StockDisponible extends Component
{
    #[On('produccion-actualizada')]
    #[On('venta-registrada')]
    public function refrescar(): void
    {
        // #[On] fuerza el re-render.
    }

    public function render()
    {
        return view('livewire.panel.despacho.stock-disponible', [
            'stock' => StockActual::query()->orderBy('producto_nombre')->get(),
        ]);
    }
}
