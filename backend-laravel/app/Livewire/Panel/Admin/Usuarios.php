<?php

namespace App\Livewire\Panel\Admin;

use App\Enums\RolUsuario;
use App\Exceptions\ReglaNegocioException;
use App\Models\Ruta;
use App\Models\Usuario;
use App\Models\Zona;
use App\Services\Usuarios\UsuarioService;
use Livewire\Component;

/**
 * Pestaña Usuarios: alta con rol y ámbito (zona/ruta/planta), y
 * suspender/reactivar. Toda la lógica vive en UsuarioService.
 */
class Usuarios extends Component
{
    public string $nombres = '';

    public string $apellidos = '';

    public string $dni = '';

    public ?string $email = null;

    public ?string $telefono = null;

    public ?string $password = null;

    public string $rol = '';

    public ?string $rutaId = null;

    public ?string $zonaId = null;

    protected function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:120'],
            'apellidos' => ['required', 'string', 'max:120'],
            'dni' => ['required', 'regex:/^[0-9]{8}$/', 'unique:usuarios,dni'],
            'email' => ['nullable', 'email', 'unique:usuarios,email'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'string', 'min:8'],
            'rol' => ['required', 'in:'.implode(',', array_column(RolUsuario::cases(), 'value'))],
            'rutaId' => ['nullable', 'exists:rutas,id', 'required_if:rol,'.RolUsuario::ACOPIADOR->value],
            'zonaId' => ['nullable', 'exists:zonas,id', 'required_if:rol,'.RolUsuario::PRODUCTOR->value],
        ];
    }

    protected function messages(): array
    {
        return [
            'dni.regex' => 'El DNI debe tener 8 dígitos.',
            'dni.unique' => 'Ya existe un usuario con ese DNI.',
            'email.unique' => 'Ese correo ya está registrado.',
            'rutaId.required_if' => 'El acopiador necesita una ruta asignada.',
            'zonaId.required_if' => 'El productor necesita una zona asignada.',
        ];
    }

    public function crear(UsuarioService $servicio): void
    {
        $this->authorize('panel-administracion');
        $this->validate();

        try {
            $servicio->crear([
                'nombres' => $this->nombres,
                'apellidos' => $this->apellidos,
                'dni' => $this->dni,
                'email' => $this->email ?: null,
                'telefono' => $this->telefono ?: null,
                'password' => $this->password ?: null,
                'rol' => $this->rol,
                'ruta_id' => $this->rutaId,
                'zona_id' => $this->zonaId,
            ]);
        } catch (ReglaNegocioException $e) {
            $this->addError('dni', $e->getMessage());

            return;
        }

        $this->reset(['nombres', 'apellidos', 'dni', 'email', 'telefono', 'password', 'rol', 'rutaId', 'zonaId']);
        session()->flash('ok', 'Usuario creado.');
    }

    public function suspender(UsuarioService $servicio, string $usuarioId): void
    {
        $this->authorize('panel-administracion');
        $this->cambiarEstado(fn () => $servicio->suspender(Usuario::findOrFail($usuarioId)));
    }

    public function reactivar(UsuarioService $servicio, string $usuarioId): void
    {
        $this->authorize('panel-administracion');
        $this->cambiarEstado(fn () => $servicio->reactivar(Usuario::findOrFail($usuarioId)));
    }

    private function cambiarEstado(callable $accion): void
    {
        try {
            $accion();
            session()->flash('ok', 'Estado del usuario actualizado.');
        } catch (ReglaNegocioException $e) {
            session()->flash('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.panel.admin.usuarios', [
            'usuarios' => Usuario::query()
                ->with(['acopiador.ruta', 'productor.zona'])
                ->orderBy('apellidos')
                ->get(),
            'roles' => RolUsuario::cases(),
            'rutas' => Ruta::query()->orderBy('nombre')->get(),
            'zonas' => Zona::query()->orderBy('nombre')->get(),
        ]);
    }
}
