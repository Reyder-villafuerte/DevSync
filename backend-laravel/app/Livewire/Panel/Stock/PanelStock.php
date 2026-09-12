<?php

namespace App\Livewire\Panel\Stock;

use App\Enums\EstadoSesionProduccion;
use App\Models\MovimientoStock;
use App\Models\Producto;
use App\Models\RegistroAcopio;
use App\Models\SesionProduccion;
use App\Models\StockActual;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Panel de existencias de la planta.
 *
 * Dos cosas distintas conviven aquí y conviene no mezclarlas:
 *
 *  - LECHE CRUDA: no es un producto del catálogo, es materia prima. No tiene
 *    movimientos_stock; su saldo sale del acopio recibido menos lo que entró a
 *    producción (litros_procesados de las sesiones no anuladas).
 *  - PRODUCTO TERMINADO (queso, yogur, pasteurizada): sale de la vista
 *    materializada `stock_actual`, que agrega el libro de movimientos. Nunca
 *    es un contador que se edite a mano.
 */
class PanelStock extends Component
{
    /** Refresco al vuelo cuando la planta produce o despacho vende. */
    #[On('produccion-actualizada')]
    #[On('venta-registrada')]
    public function refrescar(): void
    {
        unset($this->litrosAcopiados, $this->litrosProcesados);
    }

    /** Toda la leche recibida de las entregas de los socios. */
    #[Computed]
    public function litrosAcopiados(): float
    {
        return (float) RegistroAcopio::query()->where('deleted', false)->sum('litros');
    }

    /** Leche que ya entró a producción (sesiones vivas: no anuladas). */
    #[Computed]
    public function litrosProcesados(): float
    {
        return (float) SesionProduccion::query()
            ->where('deleted', false)
            ->where('estado', '!=', EstadoSesionProduccion::ANULADA->value)
            ->sum('litros_procesados');
    }

    /** Leche cruda todavía disponible en planta. */
    #[Computed]
    public function litrosDisponibles(): float
    {
        return round($this->litrosAcopiados() - $this->litrosProcesados(), 2);
    }

    /** Litros que la planta ya verificó contra el caudalímetro. */
    #[Computed]
    public function litrosRecibidosConformes(): float
    {
        return (float) RegistroAcopio::query()
            ->where('deleted', false)
            ->where('estado_recepcion', 'conforme')
            ->sum('litros_recibidos');
    }

    public function render()
    {
        // Un producto sin movimientos no aparece en la vista materializada
        // hasta que se refresca; se parte del catálogo para no esconderlo.
        $existencias = StockActual::query()->get()->keyBy('producto_id');

        $productos = Producto::query()
            ->where('deleted', false)
            ->orderBy('nombre')
            ->get()
            ->map(fn (Producto $p) => [
                'nombre' => $p->nombre,
                'unidad' => $p->unidad_medida,
                'cantidad' => (float) ($existencias[$p->id]->cantidad_actual ?? 0),
                'ultimo' => $existencias[$p->id]->ultimo_movimiento_en ?? null,
                'movimientos' => (int) ($existencias[$p->id]->total_movimientos ?? 0),
            ]);

        return view('livewire.panel.stock.panel-stock', [
            'productos' => $productos,
            'movimientos' => MovimientoStock::query()
                ->with('producto')
                ->where('deleted', false)
                ->latest('ocurrido_en')
                ->take(12)
                ->get(),
        ]);
    }
}
