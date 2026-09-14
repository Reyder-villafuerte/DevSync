<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Exceptions\ReglaNegocioException;
use App\Models\CheeseProduction;
use App\Models\InventoryStock;
use App\Services\Planta\PlantaService;

class CheeseProductionController extends Controller
{
    public function __construct(private PlantaService $planta)
    {
    }

    public function index()
    {
        $stockLeche = InventoryStock::getStock('MILK_RAW_LITERS');
        $stockQueso = InventoryStock::getStock('CHEESE_MOLD_UNITS');
        $producciones = CheeseProduction::with('supervisor')->latest()->paginate(15);

        // Moldes máximos que se pueden producir con el stock de leche disponible
        $maxMoldesPosibles = $this->planta->moldesPosibles();

        return view('produccion.index', compact('stockLeche', 'stockQueso', 'producciones', 'maxMoldesPosibles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cheese_molds_produced' => ['required', 'integer', 'min:1'],
            'batch_number' => ['nullable', 'string', 'max:50'],
        ]);

        $moldes = (int) $validated['cheese_molds_produced'];

        try {
            $this->planta->producirQueso(Auth::user(), $moldes, $validated['batch_number'] ?? null);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        $lecheRequerida = $moldes * PlantaService::LITROS_POR_MOLDE;

        return back()->with('success', "Producción registrada: {$moldes} moldes de queso agregados al stock. Se descontaron {$lecheRequerida} L de leche.");
    }
}
