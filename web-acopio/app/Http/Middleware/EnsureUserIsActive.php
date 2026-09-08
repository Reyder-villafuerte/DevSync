<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()?->fresh()?->status !== 'ACTIVO') {
            if ($request->expectsJson()) {
                abort(403, 'La cuenta no está activa.');
            }
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/login')->withErrors(['login' => 'La cuenta no está activa.']);
        }

        return $next($request);
    }
}
