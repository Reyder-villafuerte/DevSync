<?php

namespace App\Http\Middleware;

use App\Enums\RolUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a uno o más roles. Uso: ->middleware('rol:acopiador,supervisor_calidad').
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        $permitido = $usuario && $usuario->activo && in_array(
            $usuario->rol instanceof RolUsuario ? $usuario->rol->value : $usuario->rol,
            $roles,
            true,
        );

        abort_unless($permitido, 403, 'Su rol no tiene acceso a este recurso.');

        return $next($request);
    }
}
