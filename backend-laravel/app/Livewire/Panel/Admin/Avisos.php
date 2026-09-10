<?php

namespace App\Livewire\Panel\Admin;

use App\Models\Aviso;
use App\Services\Avisos\AvisoService;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Pestaña Avisos: alta con imagen (a storage, no blob) y fecha de publicación;
 * listado separando visibles de programados; retiro. Delega en AvisoService.
 */
class Avisos extends Component
{
    use WithFileUploads;

    #[Validate('required|string|max:150')]
    public string $titulo = '';

    #[Validate('required|string|max:2000')]
    public string $mensaje = '';

    #[Validate('nullable|image|max:2048')]
    public $imagen = null;

    #[Validate('required|date')]
    public $fechaPublicacion;

    #[Validate('nullable|date|after_or_equal:fechaPublicacion')]
    public $fechaExpiracion = null;

    public bool $obligatorio = false;

    public function mount(): void
    {
        $this->fechaPublicacion = now()->toDateString();
    }

    protected function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'mensaje.required' => 'El mensaje es obligatorio.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.max' => 'La imagen no puede superar 2 MB.',
            'fechaPublicacion.required' => 'Indique desde cuándo se publica.',
            'fechaExpiracion.after_or_equal' => 'La expiración no puede ser anterior a la publicación.',
        ];
    }

    public function crear(AvisoService $servicio): void
    {
        $this->authorize('panel-administracion');
        $this->validate();

        $servicio->crear([
            'titulo' => $this->titulo,
            'mensaje' => $this->mensaje,
            'fecha_publicacion' => $this->fechaPublicacion,
            'fecha_expiracion' => $this->fechaExpiracion ?: null,
            'obligatorio' => $this->obligatorio,
        ], $this->imagen, auth()->user());

        $this->reset(['titulo', 'mensaje', 'imagen', 'fechaExpiracion', 'obligatorio']);
        $this->fechaPublicacion = now()->toDateString();
        session()->flash('ok', 'Aviso creado.');
    }

    public function retirar(AvisoService $servicio, string $avisoId): void
    {
        $this->authorize('panel-administracion');
        $servicio->retirar(Aviso::findOrFail($avisoId));
        session()->flash('ok', 'Aviso retirado del mural.');
    }

    public function render()
    {
        return view('livewire.panel.admin.avisos', [
            'visibles' => Aviso::query()->vigentes()->latest('fecha_publicacion')->get(),
            'programados' => Aviso::query()
                ->where('publicado', true)
                ->where('fecha_publicacion', '>', now())
                ->latest('fecha_publicacion')
                ->get(),
        ]);
    }
}
