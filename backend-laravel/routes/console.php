<?php

use App\Services\Stock\StockService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Refresco periódico de la vista materializada de stock (respaldo al refresco
// transaccional que hace StockService tras cada movimiento).
Artisan::command('stock:refrescar', function (StockService $stock) {
    $stock->refrescarVistaStock();
    $this->info('Vista stock_actual refrescada.');
})->purpose('Refresca la vista materializada stock_actual');

Schedule::command('stock:refrescar')->everyFifteenMinutes();
