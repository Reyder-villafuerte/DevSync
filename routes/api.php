<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MobileSyncController;
use App\Http\Controllers\Api\SyncController;

// Autenticación móvil con entrega de tokens y avisos de login
Route::post('/mobile/login', [MobileSyncController::class, 'login']);

// Sincronización offline-first de MilkFlowMovil: login por DNI o correo
Route::post('/sync/login', [SyncController::class, 'login']);

// Endpoints protegidos para la app móvil (Acopiadores y Productores)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());

    // Acopiador: descargar lista de proveedores de su zona para trabajo offline
    Route::get('/mobile/collector/route', [MobileSyncController::class, 'getCollectorRoute']);

    // Acopiador: subir paquete de entregas registradas en ruta 4:30 AM
    Route::post('/mobile/collector/sync', [MobileSyncController::class, 'syncDeliveries']);

    // Productor: consultar entregas diarias y resultados de calidad Lactoscan
    Route::get('/mobile/producer/deliveries', [MobileSyncController::class, 'getProducerDeliveries']);

    // Motor de sincronización general (todas las pantallas del móvil)
    Route::post('/sync/logout', [SyncController::class, 'logout']);
    Route::match(['get', 'post'], '/sync/pull', [SyncController::class, 'pull']);
    Route::post('/sync/push', [SyncController::class, 'push']);
    Route::get('/sync/ciclos-pago', [SyncController::class, 'ciclosPago']);
});
