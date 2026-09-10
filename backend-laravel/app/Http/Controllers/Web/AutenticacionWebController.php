<?php

namespace App\Http\Controllers\Web;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Acceso al panel de escritorio por SESIÓN (guard 'web'), no por token.
 * Solo roles de oficina entran al panel.
 */
class AutenticacionWebController extends Controller
{
    private const ROLES_PANEL = [
        RolUsuario::JEFE_PRODUCCION->value,
        RolUsuario::DESPACHO_VENTAS->value,
        RolUsuario::ADMINISTRACION->value,
    ];

    public function mostrarLogin(): View
    {
        return view('panel.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'dni' => ['required', 'string', 'regex:/^[0-9]{8}$/'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(['dni' => $datos['dni'], 'password' => $datos['password'], 'activo' => true], $request->boolean('recordar'))) {
            return back()->withErrors(['dni' => 'Credenciales inválidas.'])->onlyInput('dni');
        }

        if (! in_array(Auth::user()->rol->value, self::ROLES_PANEL, true)) {
            Auth::logout();

            return back()->withErrors(['dni' => 'Su rol no tiene acceso al panel de escritorio.']);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('panel.inicio'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.login');
    }
}
