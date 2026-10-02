<?php

use App\Http\Controllers\Api\SyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Sincronización offline-first de MilkFlowMovil: login por DNI o correo
Route::post('/sync/login', [SyncController::class, 'login'])->middleware('throttle:10,1');

// Endpoints protegidos para la app móvil (Acopiadores y Productores)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user());

    // Motor de sincronización general (todas las pantallas del móvil)
    Route::post('/sync/logout', [SyncController::class, 'logout']);
    Route::match(['get', 'post'], '/sync/pull', [SyncController::class, 'pull']);
    Route::post('/sync/push', [SyncController::class, 'push']);
    Route::get('/sync/ciclos-pago', [SyncController::class, 'ciclosPago']);
});
