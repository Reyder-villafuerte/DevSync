<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\LoginRequest;
use App\Http\Resources\SesionLoginResource;
use App\Models\Dispositivo;
use App\Models\Usuario;
use App\Services\Sync\ResolutorAmbito;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Autenticación del móvil con Sanctum. Controlador delgado: toda la política
 * (dispositivo, ámbito, revocación) se resuelve con modelos y el ResolutorAmbito.
 */
class AuthController extends ApiController
{
    public function login(LoginRequest $request, ResolutorAmbito $resolutor): SesionLoginResource
    {
        $usuario = Usuario::query()->where('dni', $request->input('dni'))->first();

        if (! $usuario || ! Hash::check($request->input('password'), $usuario->password)) {
            throw ValidationException::withMessages(['dni' => 'Credenciales inválidas.']);
        }
        abort_unless($usuario->activo, 403, 'Usuario inactivo.');

        $dispositivo = Dispositivo::updateOrCreate(
            ['identificador' => $request->input('dispositivo.identificador')],
            [
                'usuario_id' => $usuario->id,
                'nombre' => $request->input('dispositivo.nombre'),
                'plataforma' => $request->input('dispositivo.plataforma', 'android'),
                'activo' => true,
            ],
        );

        // Un token por dispositivo: se revoca el anterior del mismo equipo.
        // El ability `rol:*` permite a las rutas exigir el rol sin ir a BD.
        $usuario->tokens()->where('name', "disp:{$dispositivo->id}")->delete();
        $token = $usuario->createToken("disp:{$dispositivo->id}", ['rol:'.$usuario->rol->value])->plainTextToken;

        $usuario->forceFill(['ultimo_acceso_en' => now()])->save();

        return new SesionLoginResource([
            'token' => $token,
            'usuario' => $usuario,
            'dispositivoId' => $dispositivo->id,
            'ambito' => (string) $resolutor->ambitoAsignado($usuario),
        ]);
    }

    public function me(ResolutorAmbito $resolutor): SesionLoginResource
    {
        $u = auth()->user();

        return new SesionLoginResource([
            'token' => null,
            'usuario' => $u,
            'dispositivoId' => null,
            'ambito' => (string) $resolutor->ambitoAsignado($u),
        ]);
    }

    public function logout(): JsonResponse
    {
        // Revoca únicamente el token con el que se hizo esta llamada.
        auth()->user()->currentAccessToken()->delete();

        return response()->json(['mensaje' => 'Sesión cerrada.']);
    }
}
