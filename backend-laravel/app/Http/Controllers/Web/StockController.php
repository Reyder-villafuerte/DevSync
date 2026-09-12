<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Panel de existencias (/stock). Igual que el resto de paneles: solo el shell
 * Blade; el tablero es un componente Livewire que agrega leche cruda y
 * producto terminado.
 */
class StockController extends Controller
{
    public function index(): View
    {
        return view('panel.stock');
    }
}
