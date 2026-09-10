<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Panel 3 — Administración y Gerencia (/administracion). Shell Blade con las
 * pestañas; cada pestaña es un componente Livewire que delega en Services.
 */
class AdministracionController extends Controller
{
    public function index(): View
    {
        return view('panel.administracion');
    }
}
