<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\SystemPrice;

class PriceController extends Controller
{
    /**
     * Panel de configuración de tarifas y precios de temporada
     */
    public function index()
    {
        $currentPrice = SystemPrice::current();
        $history = SystemPrice::with('updater')->latest()->paginate(10);

        return view('admin.precios.index', compact('currentPrice', 'history'));
    }

    /**
     * Actualizar precios de temporada
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'season_name' => 'required|string|max:100',
            'price_milk_base' => 'required|numeric|min:0.5|max:10',
            'price_milk_water_penalty_low' => 'required|numeric|min:0.5|max:10',
            'price_milk_water_penalty_high' => 'required|numeric|min:0.1|max:10',
            'price_cheese_provider' => 'required|numeric|min:5|max:100',
            'price_cheese_wholesale' => 'required|numeric|min:5|max:100',
            'price_cheese_local' => 'required|numeric|min:5|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        $newPrice = app(\App\Services\Sistema\SistemaService::class)
            ->actualizarTarifas(Auth::user(), $validated);

        return redirect()->route('admin.precios.index')->with('success', "Tarifas actualizadas correctamente para '{$newPrice->season_name}'.");
    }
}
