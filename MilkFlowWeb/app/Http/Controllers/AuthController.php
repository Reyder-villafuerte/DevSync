<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        // El acceso es por DNI, igual que en la app móvil. Se acepta también el
        // correo para no romper los accesos antiguos del personal de oficina.
        $request->validate([
            'dni' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $identificador = trim($request->input('dni'));
        $campo = str_contains($identificador, '@') ? 'email' : 'dni';

        $credenciales = [$campo => $identificador, 'password' => $request->input('password')];

        if (Auth::attempt($credenciales, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'dni' => 'Tu cuenta está desactivada. Comunícate con administración.',
                ])->onlyInput('dni');
            }

            // Buscar anuncios vigentes para este usuario o su rol
            $announcements = Announcement::activeForUser($user)->get();
            if ($announcements->isNotEmpty()) {
                session()->flash('login_announcements', $announcements);
            }

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'dni' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
        ])->onlyInput('dni');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
