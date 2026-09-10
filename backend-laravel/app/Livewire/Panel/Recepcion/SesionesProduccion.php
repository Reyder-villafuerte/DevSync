<?php

namespace App\Livewire\Panel\Recepcion;

use App\Enums\EstadoSesionProduccion;
use App\Exceptions\ReglaNegocioException;
use App\Models\Producto;
use App\Models\SesionProduccion;
use App\Services\Produccion\SesionProduccionService;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Sesiones de producción: iniciar un lote (con estimación de unidades en vivo)
 * y completarlo (genera el ingreso a stock, vía SesionProduccionService).
 */
class SesionesProduccion extends Component
{
    #[Validate('required|exists:productos,id')]
    public ?string $productoId = null;

    #[Validate('required|numeric|min:0.01')]
    public $litros = null;

    /** unidades reales por sesión al completar: [sesionId => int]. */
    public array $unidades = [];

    #[Computed]
    public function estimacion(): ?int
    {
        if (! $this->productoId || ! is_numeric($this->litros)) {
            return null;
        }

        $producto = Producto::find($this->productoId);

        return $producto
            ? app(SesionProduccionService::class)->estimarUnidades($producto, (float) $this->litros)
            : null;
    }

    public function iniciar(SesionProduccionService $servicio): void
    {
        $this->authorize('create', SesionProduccion::class);
        $this->validate();

        try {
            $servicio->iniciar(Producto::findOrFail($this->productoId), (float) $this->litros, auth()->user());
        } catch (ReglaNegocioException $e) {
            $this->addError('litros', $e->getMessage());

            return;
        }

        $this->reset(['productoId', 'litros']);
        session()->flash('ok', 'Sesión de producción iniciada.');
        $this->dispatch('produccion-actualizada');
    }

    public function completar(SesionProduccionService $servicio, string $sesionId): void
    {
        $sesion = SesionProduccion::findOrFail($sesionId);
        $this->authorize('completar', $sesion);

        try {
            $servicio->completar($sesion, (int) ($this->unidades[$sesionId] ?? 0));
        } catch (ReglaNegocioException $e) {
            $this->addError('completar_'.$sesionId, $e->getMessage());

            return;
        }

        unset($this->unidades[$sesionId]);
        session()->flash('ok', "Lote {$sesion->lote_codigo} completado: producción disponible para venta.");
        $this->dispatch('produccion-actualizada');
    }

    public function render()
    {
        return view('livewire.panel.recepcion.sesiones-produccion', [
            'productos' => Producto::query()->where('activo', true)->orderBy('nombre')->get(),
            'pendientes' => SesionProduccion::query()
                ->with('producto')
                ->whereIn('estado', [EstadoSesionProduccion::PLANIFICADA->value, EstadoSesionProduccion::EN_PROCESO->value])
                ->orderByDesc('fecha')
                ->get(),
            'completadas' => SesionProduccion::query()
                ->with('producto')
                ->where('estado', EstadoSesionProduccion::COMPLETADA->value)
                ->latest('completada_en')
                ->take(10)
                ->get(),
        ]);
    }
}
