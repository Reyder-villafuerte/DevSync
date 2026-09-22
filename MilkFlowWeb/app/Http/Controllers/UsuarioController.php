<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * El padrón: quién entra al sistema y con qué rol.
 *
 * Los usuarios no se borran —tienen entregas, ventas y liquidaciones colgando—
 * sino que se desactivan: dejan de poder entrar y de aparecer en las listas,
 * pero su historia queda.
 */
class UsuarioController extends Controller
{
    public const ROLES_PERMITIDOS = ['admin', 'jefe_general'];

    public function index(Request $request)
    {
        abort_unless(in_array(Auth::user()->role, self::ROLES_PERMITIDOS, true), 403);

        $roles = config('huata.roles');

        $usuarios = User::with('zone')
            ->when($request->filled('rol'), fn ($q) => $q->where('role', $request->rol))
            ->when($request->filled('estado'), fn ($q) => $q->where('is_active', $request->estado === 'activos'))
            ->when($request->filled('buscar'), function ($q) use ($request) {
                $termino = $request->buscar;

                $q->where(function ($sub) use ($termino) {
                    $sub->where('name', 'like', "%{$termino}%")
                        ->orWhere('dni', 'like', "%{$termino}%")
                        ->orWhere('email', 'like', "%{$termino}%");
                });
            })
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        $zonas = Zone::orderBy('name')->get();

        $resumen = [
            'total' => User::count(),
            'activos' => User::where('is_active', true)->count(),
            'inactivos' => User::where('is_active', false)->count(),
            'roles_en_uso' => User::distinct('role')->count('role'),
        ];

        return view('admin.usuarios.index', compact('usuarios', 'roles', 'zonas', 'resumen'));
    }

    public function store(Request $request)
    {
        abort_unless(in_array(Auth::user()->role, self::ROLES_PERMITIDOS, true), 403);

        $datos = $this->validar($request, null);
        $datos['is_active'] = true;

        $usuario = User::create($datos);

        return back()->with('success', "Usuario #{$usuario->id} guardado: {$usuario->name} · DNI {$usuario->dni} · {$this->etiquetaRol($usuario->role)}.");
    }

    public function update(Request $request, User $usuario)
    {
        abort_unless(in_array(Auth::user()->role, self::ROLES_PERMITIDOS, true), 403);

        $datos = $this->validar($request, $usuario);

        // La contraseña solo se cambia si escribieron una nueva.
        if (empty($datos['password'])) {
            unset($datos['password']);
        }

        $usuario->update($datos);

        return back()->with('success', "Usuario #{$usuario->id} guardado: {$usuario->name} · DNI {$usuario->dni} · {$this->etiquetaRol($usuario->role)}.");
    }

    /** Ni borrar ni bloquearse a uno mismo: se activa o se desactiva. */
    public function toggle(User $usuario)
    {
        abort_unless(in_array(Auth::user()->role, self::ROLES_PERMITIDOS, true), 403);

        if ($usuario->id === Auth::id()) {
            return back()->withErrors(['usuario' => 'No puedes desactivar tu propia cuenta.']);
        }

        $usuario->update(['is_active' => ! $usuario->is_active]);

        $estado = $usuario->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Usuario #{$usuario->id} {$estado}: {$usuario->name}.");
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?User $usuario): array
    {
        $id = $usuario?->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'dni' => ['required', 'string', 'max:20', Rule::unique('users', 'dni')->ignore($id)],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'role' => ['required', 'string', Rule::in(array_keys(config('huata.roles')))],
            'phone' => ['nullable', 'string', 'max:30'],
            'zone_id' => ['nullable', 'exists:zones,id'],
            'password' => [$usuario ? 'nullable' : 'required', 'string', 'min:6', 'max:100'],
        ], [
            'dni.unique' => 'Ya hay alguien registrado con ese DNI.',
            'email.unique' => 'Ya hay alguien registrado con ese correo.',
        ]);
    }

    private function etiquetaRol(string $rol): string
    {
        return config('huata.roles')[$rol] ?? $rol;
    }
}
