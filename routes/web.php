<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogoAlmacenController;
use App\Http\Controllers\ClientTypeController;
use App\Http\Controllers\CollectionController;
use App\Http\Controllers\ComprasController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldPaymentController;
use App\Http\Controllers\FinancialController;
use App\Http\Controllers\InventoryCategoryController;
use App\Http\Controllers\PaymentAuthorizationController;
use App\Http\Controllers\PlantReceptionController;
use App\Http\Controllers\PriceController;
use App\Http\Controllers\ProductCatalogController;
use App\Http\Controllers\ProductionOrderController;
use App\Http\Controllers\ProductorController;
use App\Http\Controllers\QualityController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SistemaController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\ZoneController;

// Rutas públicas de autenticación
Route::get('/', [AuthController::class, 'showLoginForm'])->name('home');
Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas autenticadas protegidas
Route::middleware('auth')->group(function () {
    // Dashboard principal con despacho por rol
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // 1. Acopio en ruta (4:30 AM)
    Route::prefix('acopio')->name('acopio.')->group(function () {
        Route::get('/', [CollectionController::class, 'index'])->name('index');
        Route::get('/historial', [CollectionController::class, 'history'])->name('historial');
        Route::post('/ruta/{route}/entrega', [CollectionController::class, 'recordProducerDelivery'])->name('delivery');
        Route::post('/ruta/{route}/descargar', [CollectionController::class, 'completeAndSendToPlant'])->name('discharge');
        Route::post('/asignar', [CollectionController::class, 'assignRoute'])->name('assign');
    });

    // 2. Planta y Verificación de Caudalímetro
    Route::prefix('planta')->name('planta.')->group(function () {
        Route::get('/verificacion', [PlantReceptionController::class, 'index'])->name('verificacion');
        Route::post('/verificar/{route}', [PlantReceptionController::class, 'verifyReception'])->name('verify');
    });

    // 3. Elaboración de Queso (1 molde = 10 L stock leche)
    Route::prefix('produccion')->name('produccion.')->group(function () {
        // 3b. Catálogo abierto: almacén por categorías, productos con receta y lotes
        Route::get('/categorias', [InventoryCategoryController::class, 'index'])->name('categorias.index');
        Route::post('/categorias', [InventoryCategoryController::class, 'store'])->name('categorias.store');
        Route::put('/categorias/{categoria}', [InventoryCategoryController::class, 'update'])->name('categorias.update');
        Route::delete('/categorias/{categoria}', [InventoryCategoryController::class, 'destroy'])->name('categorias.destroy');
        Route::post('/categorias/unidades', [InventoryCategoryController::class, 'storeUnidad'])->name('categorias.unidades.store');
        Route::put('/categorias/unidades/{unidad}', [InventoryCategoryController::class, 'updateUnidad'])->name('categorias.unidades.update');
        Route::delete('/categorias/unidades/{unidad}', [InventoryCategoryController::class, 'destroyUnidad'])->name('categorias.unidades.destroy');

        Route::get('/almacen', [CatalogoAlmacenController::class, 'index'])->name('almacen.index');
        Route::post('/almacen/insumos', [CatalogoAlmacenController::class, 'storeInsumo'])->name('almacen.insumos.store');
        Route::post('/almacen/insumos/{supply}/ajuste', [CatalogoAlmacenController::class, 'ajustarStock'])->name('almacen.insumos.ajuste');

        Route::get('/compras', [ComprasController::class, 'index'])->name('compras.index');
        Route::post('/compras', [ComprasController::class, 'store'])->name('compras.store');
        Route::put('/compras/{compra}', [ComprasController::class, 'update'])->name('compras.update');
        Route::delete('/compras/{compra}', [ComprasController::class, 'destroy'])->name('compras.destroy');

        Route::get('/productos', [ProductCatalogController::class, 'index'])->name('productos.index');
        Route::post('/productos', [ProductCatalogController::class, 'store'])->name('productos.store');
        Route::post('/productos/{product}/receta', [ProductCatalogController::class, 'updateReceta'])->name('productos.receta');
        Route::post('/productos/{product}/precios', [ProductCatalogController::class, 'updatePrecios'])->name('productos.precios');

        Route::get('/lotes', [ProductionOrderController::class, 'index'])->name('lotes.index');
        Route::post('/lotes', [ProductionOrderController::class, 'store'])->name('lotes.store');
        Route::post('/lotes/{order}/iniciar', [ProductionOrderController::class, 'start'])->name('lotes.iniciar');
        Route::post('/lotes/{order}/terminar', [ProductionOrderController::class, 'finish'])->name('lotes.terminar');
        Route::post('/lotes/{order}/cancelar', [ProductionOrderController::class, 'cancel'])->name('lotes.cancelar');
    });

    // 4. Ventas y Despacho (Tarifas S/ 18, 19, 20 + Solo efectivo + Recibos)
    Route::prefix('ventas')->name('ventas.')->group(function () {
        Route::get('/', [SalesController::class, 'index'])->name('index');
        Route::post('/cierre-caja', [SalesController::class, 'closeCashRegister'])->name('close-cash');
        Route::get('/recibos', [SalesController::class, 'receiptsHistory'])->name('receipts');
        Route::get('/nueva', [SalesController::class, 'create'])->name('create');
        Route::post('/', [SalesController::class, 'store'])->name('store');
        Route::get('/recibo/{sale}', [SalesController::class, 'showReceipt'])->name('receipt');
        // Endpoints auxiliares para cálculo y autocompletado en caja
        Route::get('/clientes/buscar', [SalesController::class, 'searchCustomer'])->name('customers.search');
        Route::get('/clientes/reconocer', [SalesController::class, 'matchCustomer'])->name('customers.match');
        Route::get('/calcular-precio', [SalesController::class, 'calculatePrice'])->name('calculate-price');
    });

    // 5. Inspector de Calidad (Lactoscan y Citas Técnicas)
    Route::prefix('calidad')->name('calidad.')->group(function () {
        Route::get('/', [QualityController::class, 'index'])->name('index');
        Route::get('/analisis', fn () => redirect()->route('calidad.index'));
        Route::post('/analisis', [QualityController::class, 'storeAnalysis'])->name('analysis.store');
        Route::post('/analisis/{analysis}/cita', [QualityController::class, 'scheduleVisit'])->name('visit.schedule');
        Route::post('/cita/{visit}/completar', [QualityController::class, 'completeVisit'])->name('visit.complete');
    });

    // 6. Zonas de Huata y Solicitudes de Cambio
    Route::prefix('zonas')->name('zonas.')->group(function () {
        Route::get('/', [ZoneController::class, 'index'])->name('index');
        Route::get('/solicitudes', [ZoneController::class, 'solicitudes'])->name('solicitudes');
        Route::post('/solicitar-cambio', [ZoneController::class, 'requestChange'])->name('request-change');
        Route::post('/solicitud/{zoneChangeRequest}/revisar', [ZoneController::class, 'reviewRequest'])->name('review-request');
    });

    // 7. Anuncios y Avisos en Login
    Route::prefix('anuncios')->name('anuncios.')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index'])->name('index');
        Route::post('/', [AnnouncementController::class, 'store'])->name('store');
    });

    // 8. Portal y Módulos Exclusivos del Proveedor / Productor
    Route::prefix('productor')->name('productor.')->group(function () {
        Route::get('/acopio', [ProductorController::class, 'acopio'])->name('acopio');
        Route::get('/zonas', [ProductorController::class, 'zonas'])->name('zonas');
        Route::post('/zonas/solicitar', [ProductorController::class, 'solicitarCambioZona'])->name('zonas.solicitar');
        Route::get('/descuentos', [ProductorController::class, 'descuentos'])->name('descuentos');
        Route::get('/pagos', [ProductorController::class, 'pagos'])->name('pagos');
        Route::get('/pagos/{id}/recibo', [ProductorController::class, 'reciboPago'])->name('pagos.recibo');
        Route::get('/calidad', [ProductorController::class, 'calidad'])->name('calidad');
        Route::post('/liquidar/{user}', [ProductorController::class, 'liquidarSemana'])->name('liquidar');
    });

    // 9. Administración: Tarifas y Precios de Temporada
    Route::prefix('admin/usuarios')->name('admin.usuarios.')->group(function () {
        Route::get('/', [UsuarioController::class, 'index'])->name('index');
        Route::post('/', [UsuarioController::class, 'store'])->name('store');
        Route::put('/{usuario}', [UsuarioController::class, 'update'])->name('update');
        Route::post('/{usuario}/estado', [UsuarioController::class, 'toggle'])->name('toggle');
    });

    Route::get('/admin/roles', [SistemaController::class, 'roles'])->name('admin.roles.index');
    Route::get('/admin/catalogo', [SistemaController::class, 'catalogo'])->name('admin.catalogo.index');

    Route::prefix('admin/tipos-cliente')->name('admin.tipos-cliente.')->group(function () {
        Route::get('/', [ClientTypeController::class, 'index'])->name('index');
        Route::post('/', [ClientTypeController::class, 'store'])->name('store');
        Route::put('/{tipo}', [ClientTypeController::class, 'update'])->name('update');
        Route::delete('/{tipo}', [ClientTypeController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin/precios')->name('admin.precios.')->group(function () {
        Route::get('/', [PriceController::class, 'index'])->name('index');
        Route::post('/', [PriceController::class, 'update'])->name('update');
        Route::post('/tarifas', [PriceController::class, 'storeRegla'])->name('tarifas.store');
        Route::put('/tarifas/{regla}', [PriceController::class, 'updateRegla'])->name('tarifas.update');
        Route::delete('/tarifas/{regla}', [PriceController::class, 'destroyRegla'])->name('tarifas.destroy');
    });

    // 10. Administración: Panel de Autorización y Liquidación de Pagos
    Route::prefix('admin/pagos')->name('admin.pagos.')->group(function () {
        Route::get('/autorizacion', [PaymentAuthorizationController::class, 'index'])->name('autorizacion');
        Route::post('/autorizar/{producer}', [PaymentAuthorizationController::class, 'authorizeSingle'])->name('authorize-single');
        Route::post('/autorizar-todos', [PaymentAuthorizationController::class, 'authorizeAll'])->name('authorize-all');
    });

    // 11. Rol Pagador de Campo: Entrega de Sobres en Efectivo (Viernes en Ruta)
    Route::prefix('pagos/ruta')->name('pagos.ruta.')->group(function () {
        Route::get('/', [FieldPaymentController::class, 'index'])->name('index');
        Route::post('/pagar/{producer}', [FieldPaymentController::class, 'payProducer'])->name('pay');
        Route::get('/recibo/{settlement}', [FieldPaymentController::class, 'receipt'])->name('receipt');
        Route::get('/historial', [FieldPaymentController::class, 'history'])->name('history');
    });

    // 12. Administración: Flujo de Caja y Control Financiero (Ingresos vs Egresos)
    Route::prefix('admin/finanzas')->name('admin.finanzas.')->group(function () {
        Route::get('/', [FinancialController::class, 'index'])->name('index');
        Route::post('/egreso', [FinancialController::class, 'storeExpense'])->name('expense.store');
    });
});
