<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AvisoController;
use App\Http\Controllers\Api\CorrelativoController;
use App\Http\Controllers\Api\InspeccionController;
use App\Http\Controllers\Api\JornadaController;
use App\Http\Controllers\Api\SyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API móvil (Kotlin Multiplatform / Ktor Client)
|--------------------------------------------------------------------------
| Cada endpoint se restringe con:
|   - auth:sanctum         (token de dispositivo)
|   - rol:<roles>          (gate grueso por rol; alias de VerificarRol)
|   - policies             (autorización fina sobre el recurso concreto,
|                           invocadas con $this->authorize(...) en el controlador)
| Los controladores son delgados y delegan en app/Services.
*/

Route::middleware('api.json')->group(function () {

    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {

        // --- Comunes a cualquier usuario autenticado ---
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);

        // --- Sincronización (todos los roles operativos; el ámbito filtra) ---
        Route::get('sync/pull', [SyncController::class, 'pull']);
        Route::post('sync/push', [SyncController::class, 'push']);

        // --- Acopiador ---
        Route::middleware('rol:acopiador,administracion')->group(function () {
            Route::post('jornadas/{jornada}/cerrar', [JornadaController::class, 'cerrar']);
        });

        // --- Supervisor de Calidad ---
        Route::middleware('rol:supervisor_calidad,administracion')->group(function () {
            Route::post('inspecciones', [InspeccionController::class, 'store']);
        });

        // --- Despacho y Ventas ---
        Route::middleware('rol:despacho_ventas,administracion')->group(function () {
            Route::post('correlativos/reservar', [CorrelativoController::class, 'reservar']);
            Route::post('correlativos/cerrar-dia', [CorrelativoController::class, 'cerrarDia']);
        });

        // --- Productor ---
        Route::middleware('rol:productor,administracion')->group(function () {
            Route::get('avisos/activos', [AvisoController::class, 'activos']);
            Route::post('avisos/{aviso}/visto', [AvisoController::class, 'marcarVisto']);
        });
    });
});
