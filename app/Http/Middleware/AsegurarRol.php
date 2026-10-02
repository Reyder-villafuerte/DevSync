<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Deja pasar solo a los roles indicados en la ruta.
 *
 * Uso: ->middleware('rol:admin,jefe_general')
 *
 * El menú lateral ya oculta lo que un rol no debe ver, pero eso no basta:
 * sin este filtro cualquier usuario podría llamar la URL directamente.
 */
class AsegurarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        abort_unless($usuario && in_array($usuario->role, $roles, true), 403, 'No tienes permiso para realizar esta acción.');

        return $next($request);
    }
}
