<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Dispositivo;
use Illuminate\Http\Request;

/**
 * Base de los controladores de la API móvil. Resuelve el dispositivo a partir
 * del token de Sanctum (que se emite con nombre `disp:<uuid>`), de modo que un
 * cliente no pueda hacerse pasar por otro equipo enviando otro id en el body.
 */
abstract class ApiController extends Controller
{
    protected function dispositivoActual(Request $request): Dispositivo
    {
        $token = $request->user()->currentAccessToken();
        $nombre = $token->name ?? '';

        abort_unless(str_starts_with($nombre, 'disp:'), 403, 'El token no está asociado a un dispositivo.');

        $id = substr($nombre, 5);

        /** @var Dispositivo $dispositivo */
        $dispositivo = Dispositivo::query()->where('id', $id)->firstOrFail();
        abort_unless($dispositivo->usuario_id === $request->user()->id && $dispositivo->activo, 403, 'Dispositivo no habilitado.');

        return $dispositivo;
    }
}
