<?php

namespace App\Http\Controllers;

use App\Actions\RegisterWorker;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(RegisterRequest $request, RegisterWorker $action)
    {
        $action->execute($request->validated());

        return redirect('/registro/pendiente')->with('status', 'Solicitud registrada correctamente.');
    }

    public function login(Request $request)
    {
        $data = $request->validate(['login' => 'required|string|max:190', 'password' => 'required|string']);
        $user = User::where('email', mb_strtolower($data['login']))->orWhere('username', $data['login'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'Las credenciales no son correctas.']);
        }
        if ($user->status !== 'ACTIVO') {
            throw ValidationException::withMessages(['login' => match ($user->status) {
                'PENDIENTE' => 'Tu cuenta está pendiente de aprobación por el administrador.','RECHAZADO' => 'Tu solicitud de registro fue rechazada.','INACTIVO' => 'Tu cuenta está inactiva. Contacta al administrador.',default => 'Tu cuenta no está habilitada.'
            }]);
        }
        if ($user->roles()->doesntExist()) {
            throw ValidationException::withMessages(['login' => 'La cuenta aún no tiene un rol asignado.']);
        }
        Auth::login($user);
        $request->session()->regenerate();

        return redirect($user->dashboard());
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        Password::sendResetLink($request->only('email'));

        return back()->with('status', 'Si el correo está registrado, recibirás instrucciones para restablecer tu contraseña.');
    }

    public function reset(Request $request)
    {
        $request->validate(['token' => 'required', 'email' => 'required|email', 'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::min(10)->mixedCase()->numbers()->symbols()]]);
        $status = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $status === Password::PASSWORD_RESET ? redirect('/login')->with('status', 'Contraseña actualizada.') : back()->withErrors(['email' => 'El enlace no es válido o ha vencido.']);
    }
}
