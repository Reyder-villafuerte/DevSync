<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Venta;
use Illuminate\View\View;

/**
 * Panel 2 — Despacho y Ventas (/despacho). Shell Blade + componentes Livewire.
 * `comprobante` es la vista imprimible de una venta ya emitida.
 */
class DespachoController extends Controller
{
    public function index(): View
    {
        return view('panel.despacho');
    }

    public function comprobante(Venta $venta): View
    {
        $this->authorize('panel-despacho');

        $venta->load(['cliente', 'detalles.producto']);

        return view('panel.ventas.comprobante', compact('venta'));
    }
}
