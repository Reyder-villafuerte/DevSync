<?php

namespace App\Livewire\Panel\Recepcion;

use App\Enums\EstadoSesionProduccion;
use App\Models\SesionProduccion;
use App\Services\Produccion\RendimientoService;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Semáforo RN-08: rendimiento agregado sobre las sesiones ya completadas.
 * Se refresca solo (polling) y cuando otra parte del panel completa producción.
 */
class SemaforoRendimiento extends Component
{
    #[On('produccion-actualizada')]
    public function refrescar(): void
    {
        // El atributo #[On] ya fuerza el re-render; método vacío a propósito.
    }

    public function render()
    {
        $sesiones = SesionProduccion::query()
            ->with('producto')
            ->where('estado', EstadoSesionProduccion::COMPLETADA->value)
            ->get();

        return view('livewire.panel.recepcion.semaforo-rendimiento', [
            'semaforo' => app(RendimientoService::class)->semaforo($sesiones),
        ]);
    }
}
