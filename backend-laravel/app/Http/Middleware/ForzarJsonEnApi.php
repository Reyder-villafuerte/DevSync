<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// El cliente Ktor no siempre envía Accept: application/json; lo forzamos para
// que los errores de validación y de auth salgan como JSON y no como HTML.
class ForzarJsonEnApi
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
