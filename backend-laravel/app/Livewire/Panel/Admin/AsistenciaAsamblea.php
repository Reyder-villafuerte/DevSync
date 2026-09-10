<?php

namespace App\Livewire\Panel\Admin;

use App\Exceptions\ReglaNegocioException;
use App\Models\Asamblea;
use App\Services\Asamblea\AsambleaService;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * Pestaña Asistencia a asamblea: buscador por DNI o nombre (ignora acentos),
 * marcado de presencia y contador de quórum. Toda la lógica (padrón congelado,
 * quórum, normalización) vive en AsambleaService.
 */
class AsistenciaAsamblea extends Component
{
    public string $busqueda = '';

    #[Validate('required|string|min:3')]
    public string $nuevaTitulo = '';

    #[Validate('required|date')]
    public $nuevaFecha;

    public ?string $nuevoLugar = null;

    public function mount(): void
    {
        $this->nuevaFecha = now()->toDateString();
    }

    private function asambleaActiva(): ?Asamblea
    {
        return Asamblea::query()->where('estado', 'en_curso')->latest('fecha')->first();
    }

    public function abrir(AsambleaService $servicio): void
    {
        $this->authorize('panel-administracion');
        $this->validate();

        $asamblea = Asamblea::create([
            'titulo' => $this->nuevaTitulo,
            'fecha' => $this->nuevaFecha,
            'lugar' => $this->nuevoLugar,
            'tipo' => 'ordinaria',
            'estado' => 'convocada',
        ]);

        try {
            $servicio->abrirRegistro($asamblea);
        } catch (ReglaNegocioException $e) {
            $this->addError('nuevaTitulo', $e->getMessage());

            return;
        }

        $this->reset(['nuevaTitulo', 'nuevoLugar']);
        session()->flash('ok', 'Asamblea abierta; padrón y quórum congelados.');
    }

    public function marcar(AsambleaService $servicio, string $dni, ?string $nombre = null): void
    {
        $this->authorize('panel-administracion');
        $asamblea = $this->asambleaActiva();
        if (! $asamblea) {
            return;
        }

        try {
            $servicio->registrarAsistencia($asamblea, $dni, $nombre, auth()->id());
        } catch (ReglaNegocioException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->busqueda = '';
    }

    public function render()
    {
        $asamblea = $this->asambleaActiva()?->fresh();

        return view('livewire.panel.admin.asistencia-asamblea', [
            'asamblea' => $asamblea,
            'resultados' => strlen(trim($this->busqueda)) >= 2
                ? app(AsambleaService::class)->buscarPadron($this->busqueda)
                : collect(),
            'asistencias' => $asamblea
                ? $asamblea->asistencias()->where('deleted', false)->latest('registrado_en')->get()
                : collect(),
        ]);
    }
}
