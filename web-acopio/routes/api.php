<?php

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/login', [ApiController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/registro', [ApiController::class, 'register'])->middleware('throttle:5,1');
    Route::middleware(['auth:sanctum', 'active', 'throttle:120,1'])->group(function () {
        Route::post('/logout', [ApiController::class, 'logout']);
        Route::get('/productores', [ApiController::class, 'list'])->defaults('module', 'producers')->middleware('role:admin,acopiador');
        Route::get('/entregas', [ApiController::class, 'list'])->defaults('module', 'entregas')->middleware('role:admin,acopiador,supervisor,productor');
        Route::post('/entregas', [ApiController::class, 'create'])->defaults('module', 'entregas')->middleware('role:admin,acopiador');
        Route::get('/calidad', [ApiController::class, 'list'])->defaults('module', 'calidad')->middleware('role:admin,supervisor,productor');
        Route::post('/calidad', [ApiController::class, 'create'])->defaults('module', 'calidad')->middleware('role:admin,supervisor');
        Route::get('/produccion', [ApiController::class, 'list'])->defaults('module', 'lotes')->middleware('role:admin,produccion');
        Route::post('/produccion', [ApiController::class, 'create'])->defaults('module', 'lotes')->middleware('role:admin,produccion');
        Route::get('/notificaciones', [ApiController::class, 'notifications']);
    });
});
