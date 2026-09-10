<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Portada del panel de escritorio. Cada panel de rol tiene su propio
 * controlador (RecepcionController, DespachoController, AdministracionController);
 * toda la lógica vive en componentes Livewire o en Services.
 */
class PanelController extends Controller
{
    public function inicio(): View
    {
        $rol = auth()->user()->rol;

        return view('panel.inicio', compact('rol'));
    }
}
