<?php

use App\Http\Middleware\ForzarJsonEnApi;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // El grupo API usa el guard de tokens de Sanctum; la web usa sesión.
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'rol' => \App\Http\Middleware\VerificarRol::class,
            'api.json' => ForzarJsonEnApi::class,
        ]);

        // Invitados sin sesión van al login del panel (no existe una ruta "login").
        $middleware->redirectGuestsTo(fn () => route('panel.login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Las excepciones de dominio ya devuelven su propia respuesta HTTP.
    })->create();
