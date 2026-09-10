<?php

namespace App\Livewire\Panel\Admin;

use App\Exceptions\ReglaNegocioException;
use App\Models\SolicitudCambioZona;
use App\Services\Padron\SolicitudCambioZonaService;
use Livewire\Component;

/**
 * Pestaña Solicitudes de cambio de ruta: pendientes con aprobar/rechazar.
 * Al aprobar, SolicitudCambioZonaService mueve la zona del productor.
 */
class SolicitudesCambioRuta extends Component
{
    /** comentario de resolución por solicitud: [id => texto]. */
    public array $comentario = [];

    public function aprobar(SolicitudCambioZonaService $servicio, string $solicitudId): void
    {
        $this->authorize('resolver', SolicitudCambioZona::class);
        $this->resolver(fn ($s) => $servicio->aprobar($s, auth()->user(), $this->comentario[$solicitudId] ?? null), $solicitudId, 'aprobada');
    }

    public function rechazar(SolicitudCambioZonaService $servicio, string $solicitudId): void
    {
        $this->authorize('resolver', SolicitudCambioZona::class);
        $this->resolver(fn ($s) => $servicio->rechazar($s, auth()->user(), $this->comentario[$solicitudId] ?? null), $solicitudId, 'rechazada');
    }

    private function resolver(callable $accion, string $solicitudId, string $resultado): void
    {
        $solicitud = SolicitudCambioZona::findOrFail($solicitudId);

        try {
            $accion($solicitud);
        } catch (ReglaNegocioException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        unset($this->comentario[$solicitudId]);
        session()->flash('ok', "Solicitud {$resultado}.");
    }

    public function render()
    {
        return view('livewire.panel.admin.solicitudes-cambio-ruta', [
            'pendientes' => SolicitudCambioZona::query()
                ->pendientes()
                ->with(['productor', 'zonaActual.ruta', 'zonaSolicitada.ruta'])
                ->orderBy('created_at')
                ->get(),
            'resueltas' => SolicitudCambioZona::query()
                ->where('estado', '!=', 'pendiente')
                ->with(['productor', 'zonaSolicitada'])
                ->latest('resuelto_en')
                ->take(10)
                ->get(),
        ]);
    }
}
