<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        abort_unless(collect($roles)->contains(fn ($role) => $request->user()?->hasRole($role)), 403, 'No tienes acceso a este módulo.');

        return $next($request);
    }
}
