<?php

namespace App\Http\Controllers;

use App\Models\SystemPrice;
use App\Services\Sistema\SistemaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PriceController extends Controller
{
    /**
     * Panel de configuración de tarifas y precios de temporada
     */
    public function index()
    {
        abort_unless(Auth::user()->role === 'admin', 403);
        $currentPrice = SystemPrice::current();
        $history = SystemPrice::with('updater')->latest()->paginate(10);

        return view('admin.precios.index', compact('currentPrice', 'history'));
    }

    /**
     * Actualizar precios de temporada
     */
    public function update(Request $request)
    {
        abort_unless(Auth::user()->role === 'admin', 403);

        $validated = $request->validate([
            'season_name' => 'required|string|max:100',
            'price_milk_base' => 'required|numeric|gt:0|decimal:0,2|max:9999.99',
            'price_milk_water_penalty_low' => 'required|numeric|gt:0|decimal:0,2|max:9999.99',
            'price_milk_water_penalty_high' => 'required|numeric|gt:0|decimal:0,2|max:9999.99',
            'price_cheese_provider' => 'required|numeric|gt:0|decimal:0,2|max:9999.99',
            'price_cheese_wholesale' => 'required|numeric|gt:0|decimal:0,2|max:9999.99',
            'price_cheese_local' => 'required|numeric|gt:0|decimal:0,2|max:9999.99',
            'notes' => 'nullable|string|max:500',
        ]);

        $newPrice = app(SistemaService::class)
            ->actualizarTarifas(Auth::user(), $validated);

        return redirect()->route('admin.precios.index')->with('success', "Tarifas actualizadas correctamente para '{$newPrice->season_name}'.");
    }
}
