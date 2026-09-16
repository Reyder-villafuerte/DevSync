<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RouteClosureController;
use App\Http\Controllers\SyncController;
use App\Http\Controllers\WorkerController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => redirect('/login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
    Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::view('/registro/pendiente', 'auth.pending');
    Route::view('/auth/forgot-password', 'auth.forgot')->name('password.request');
    Route::post('/auth/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', fn($token) => view('auth.reset', compact('token')))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:5,1');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/acopiador/offline', [SyncController::class, 'offline'])->middleware(['auth', 'active', 'role:acopiador']);
Route::post('/acopiador/sync/enviar', [SyncController::class, 'send'])->defaults('module', 'entregas')->middleware(['auth', 'active', 'role:acopiador', 'throttle:120,1']);
foreach (['acopiador', 'produccion'] as $role) {
    Route::get('/' . $role . '/sync', [SyncController::class, 'index'])->middleware(['auth', 'active', 'role:' . $role]);
}
foreach (['admin' => 'reports', 'supervisor' => 'reportes', 'produccion' => 'reportes', 'despacho' => 'reportes'] as $role => $path) {
    Route::get('/' . $role . '/' . $path, [ReportController::class, 'index'])->middleware(['auth', 'active', 'role:' . $role, 'permission:reports.view']);
}
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/notificaciones', [AccountController::class, 'notifications']);
    Route::post('/notificaciones/{id}/leer', [AccountController::class, 'read']);
    Route::get('/productor/perfil', [AccountController::class, 'profile'])->middleware('role:productor');
    Route::patch('/productor/perfil', [AccountController::class, 'update'])->middleware('role:productor');
    Route::get('/admin/settings', [AccountController::class, 'settings'])->middleware('role:admin');
    Route::post('/admin/settings', [AccountController::class, 'saveSettings'])->middleware('role:admin');
    Route::get('/acopiador/ruta/cerrar', [RouteClosureController::class, 'index'])->middleware('role:acopiador');
    Route::post('/acopiador/ruta/cerrar', [RouteClosureController::class, 'close'])->middleware('role:acopiador');
});
foreach (['admin' => ['producers', 'acopiadores', 'zones', 'sectors', 'routes', 'entregas', 'liquidaciones', 'comunicados', 'rotaciones', 'audit'], 'acopiador' => ['producers', 'entregas'], 'supervisor' => ['calidad', 'problemas', 'capacitaciones'], 'produccion' => ['lotes'], 'despacho' => ['ventas', 'stock'], 'productor' => ['entregas', 'calidad', 'liquidaciones', 'comunicados', 'rotaciones']] as $role => $modules) {
    Route::prefix($role)->middleware(['auth', 'active', 'role:' . $role])->group(function () use ($modules) {
        Route::get('/dashboard', DashboardController::class);
        foreach ($modules as $module) {
            Route::get('/' . $module, [ModuleController::class, 'index'])->defaults('module', $module);
            Route::get('/' . $module . '/create', [ModuleController::class, 'form'])->defaults('module', $module);
            Route::get('/' . $module . '/{id}/edit', [ModuleController::class, 'form'])->defaults('module', $module)->whereNumber('id');
            Route::get('/' . $module . '/{id}', [ModuleController::class, 'show'])->defaults('module', $module)->whereNumber('id');
            Route::post('/' . $module, [ModuleController::class, 'save'])->defaults('module', $module);
            Route::patch('/' . $module . '/{id}', [ModuleController::class, 'save'])->defaults('module', $module)->whereNumber('id');
        }
    });
}
Route::post('/admin/liquidaciones/{id}/pagar', [ModuleController::class, 'pay'])->middleware(['auth', 'active', 'role:admin', 'permission:settlements.manage']);
Route::post('/admin/rotaciones/{id}/revisar', [ModuleController::class, 'rotate'])->middleware(['auth', 'active', 'role:admin']);
Route::get('/supervisor/inspecciones', fn() => redirect('/supervisor/calidad?hoy=1'))->middleware(['auth', 'active', 'role:supervisor']);
Route::get('/produccion/hoy', fn() => redirect('/produccion/lotes?hoy=1'))->middleware(['auth', 'active', 'role:produccion']);
Route::middleware(['auth', 'active', 'role:admin', 'permission:users.manage'])->group(function () {
    Route::get('/admin/solicitudes', [WorkerController::class, 'index']);
    Route::get('/admin/users', [WorkerController::class, 'index']);
    Route::get('/admin/solicitudes/{user}', [WorkerController::class, 'show']);
    Route::post('/admin/solicitudes/{user}', [WorkerController::class, 'review']);
    Route::patch('/admin/users/{user}', [WorkerController::class, 'update']);
});
