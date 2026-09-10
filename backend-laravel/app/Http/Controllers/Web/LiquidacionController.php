<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Liquidacion;
use App\Models\SemanaPago;
use Illuminate\View\View;

/**
 * GET /liquidaciones-semanales  -> índice de semanas y sus liquidaciones.
 * La generación real se dispara desde el componente Livewire
 * LiquidacionesSemanales, que delega en LiquidacionService.
 */
class LiquidacionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Liquidacion::class);

        return view('panel.liquidaciones.index', [
            'semanas' => SemanaPago::query()
                ->withCount('liquidaciones')
                ->latest('fecha_inicio')
                ->paginate(15),
        ]);
    }

    public function show(SemanaPago $semana): View
    {
        $this->authorize('viewAny', Liquidacion::class);

        $semana->load([
            'liquidaciones' => fn ($q) => $q->with(['productor', 'detalles'])->orderByDesc('monto_neto'),
        ]);

        return view('panel.liquidaciones.show', compact('semana'));
    }
}
