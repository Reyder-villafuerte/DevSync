<?php

use App\Http\Controllers\Web\AdministracionController;
use App\Http\Controllers\Web\AutenticacionWebController;
use App\Http\Controllers\Web\DespachoController;
use App\Http\Controllers\Web\LiquidacionController;
use App\Http\Controllers\Web\PanelController;
use App\Http\Controllers\Web\RecepcionController;
use App\Http\Controllers\Web\StockController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('panel.inicio'));

// --- Autenticación por sesión (panel de escritorio) ---
Route::get('login', [AutenticacionWebController::class, 'mostrarLogin'])->name('panel.login');
Route::post('login', [AutenticacionWebController::class, 'login'])->middleware('throttle:10,1');
Route::post('logout', [AutenticacionWebController::class, 'logout'])->name('panel.logout');

Route::middleware('auth:web')->group(function () {
    Route::get('panel', [PanelController::class, 'inicio'])->name('panel.inicio');

    // --- Panel 1: Jefatura de Planta ---
    // `can:` aborta con 403 (no redirige) si el rol no tiene la capacidad.
    Route::middleware('can:panel-jefatura-planta')->group(function () {
        Route::get('recepcion', [RecepcionController::class, 'index'])->name('panel.recepcion');
        Route::get('stock', [StockController::class, 'index'])->name('panel.stock');
    });

    // --- Panel 2: Despacho y Ventas ---
    Route::middleware('can:panel-despacho')->group(function () {
        Route::get('despacho', [DespachoController::class, 'index'])->name('panel.despacho');
        Route::get('ventas/{venta}/comprobante', [DespachoController::class, 'comprobante'])->name('ventas.comprobante');
    });

    // --- Panel 3: Administración y Gerencia ---
    Route::middleware('can:panel-administracion')->group(function () {
        Route::get('administracion', [AdministracionController::class, 'index'])->name('panel.administracion');
        Route::get('liquidaciones-semanales', [LiquidacionController::class, 'index'])->name('liquidaciones.index');
        Route::get('liquidaciones-semanales/{semana}', [LiquidacionController::class, 'show'])->name('liquidaciones.show');
    });
});
