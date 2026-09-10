<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Panel 1 — Jefatura de Planta (/recepcion). Sólo renderiza el shell Blade;
 * cada tablero es un componente Livewire que delega en Services.
 */
class RecepcionController extends Controller
{
    public function index(): View
    {
        return view('panel.recepcion');
    }
}
