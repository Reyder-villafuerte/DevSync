<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionRoute;
use App\Models\PlantReception;
use App\Models\InventoryStock;
use App\Services\Planta\PlantaService;

class PlantReceptionController extends Controller
{
    public function __construct(private PlantaService $planta)
    {
    }

    // Mostrar interfaz de verificación en planta
    public function index()
    {
        $today = date('Y-m-d');
        $routes = CollectionRoute::where('date', $today)
            ->with(['zone', 'collector', 'records.producer', 'reception'])
            ->get();

        $stockLeche = InventoryStock::getStock('MILK_RAW_LITERS');

        return view('planta.verificacion', compact('routes', 'stockLeche', 'today'));
    }

    // Jefe de Producción verifica descarga de acopiador mediante caudalímetro
    public function verifyReception(Request $request, CollectionRoute $route)
    {
        $validated = $request->validate([
            'flowmeter_liters' => ['required', 'numeric', 'min:0'],
            'verification_status' => ['required', 'in:verificado,incompleto,con_observacion'],
            'observation' => ['nullable', 'string', 'max:500'],
        ]);

        $user = Auth::user();
        $flowmeterLiters = (float) $validated['flowmeter_liters'];

        $previousReception = PlantReception::where('collection_route_id', $route->id)->first();
        $previousFlowmeter = $previousReception ? (float)$previousReception->flowmeter_liters : 0.0;

        try {
            $this->planta->verificarRecepcion(
                $route,
                $user,
                $flowmeterLiters,
                $validated['verification_status'],
                $validated['observation'] ?? null
            );
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        $deltaStock = $flowmeterLiters - $previousFlowmeter;

        $msg = $previousReception
            ? "Medición corregida: se registraron {$flowmeterLiters} L en caudalímetro (ajuste neto: " . ($deltaStock >= 0 ? "+{$deltaStock}" : "{$deltaStock}") . " L en stock)."
            : "Recepción verificada con caudalímetro. Ingresaron {$flowmeterLiters} L al stock de leche.";

        return back()->with('success', $msg);
    }
}
