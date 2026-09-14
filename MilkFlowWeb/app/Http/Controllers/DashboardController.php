<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Models\InventoryStock;
use App\Models\CollectionRoute;
use App\Models\CollectionRecord;
use App\Models\PlantReception;
use App\Models\CheeseProduction;
use App\Models\Sale;
use App\Models\LactoscanAnalysis;
use App\Models\Zone;
use App\Models\User;
use App\Models\ZoneChangeRequest;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if ($user && $user->role === 'productor') {
            return redirect()->route('productor.acopio');
        }

        if ($user && $user->role === 'acopiador') {
            return redirect()->route('acopio.index');
        }

        if ($user && $user->role === 'jefe_produccion') {
            return redirect()->route('planta.verificacion');
        }

        if ($user && $user->role === 'pagador_campo') {
            return redirect()->route('pagos.ruta.index');
        }

        $today = date('Y-m-d');

        $stockLeche = InventoryStock::getStock('MILK_RAW_LITERS');
        $stockQueso = InventoryStock::getStock('CHEESE_MOLD_UNITS');

        $data = [
            'user' => $user,
            'today' => $today,
            'stockLeche' => $stockLeche,
            'stockQueso' => $stockQueso,
        ];

        switch ($user->role) {
            case 'productor':
                $data['misEntregas'] = CollectionRecord::where('producer_id', $user->id)
                    ->with('route.zone')
                    ->latest()
                    ->take(15)
                    ->get();
                $data['misAnalisis'] = LactoscanAnalysis::where('producer_id', $user->id)
                    ->latest()
                    ->take(5)
                    ->get();
                $data['totalLitros'] = CollectionRecord::where('producer_id', $user->id)->sum('liters');
                $data['miZona'] = $user->zone;
                $data['zonasDisponibles'] = Zone::where('is_active', true)->get();
                break;

            case 'acopiador':
                $data['miRutaHoy'] = CollectionRoute::where('collector_id', $user->id)
                    ->where('date', $today)
                    ->with(['zone.producers', 'records.producer', 'reception'])
                    ->first();
                $data['historialRutas'] = CollectionRoute::where('collector_id', $user->id)
                    ->with('zone')
                    ->latest('date')
                    ->take(10)
                    ->get();
                break;

            case 'jefe_produccion':
                $data['rutasPorVerificar'] = CollectionRoute::where('date', $today)
                    ->with(['zone', 'collector', 'records.producer', 'reception'])
                    ->get();
                $data['produccionesRecientes'] = CheeseProduction::latest()->take(10)->get();
                break;

            case 'personal_venta':
                $data['ventasHoy'] = Sale::whereDate('sold_at', $today)->with('customer')->latest()->get();
                $data['totalVentasHoy'] = Sale::whereDate('sold_at', $today)->sum('total_amount');
                $data['quesosVendidosHoy'] = Sale::whereDate('sold_at', $today)->sum('cheese_molds_quantity');
                break;

            case 'inspector_calidad':
                $data['ultimosAnalisis'] = LactoscanAnalysis::with(['producer.zone', 'technicalVisits'])
                    ->latest()
                    ->take(15)
                    ->get();
                $data['productores'] = User::where('role', 'productor')->with('zone')->orderBy('name')->get();
                break;

            case 'admin':
            case 'jefe_general':
            case 'personal_pago':
            default:
                $data['rutasHoy'] = CollectionRoute::where('date', $today)->with(['zone', 'collector'])->get();
                $data['litrosAcopiadosHoy'] = CollectionRecord::whereHas('route', fn($q) => $q->where('date', $today))->sum('liters');
                $data['totalVentasMonto'] = Sale::sum('total_amount');
                $data['totalProductores'] = User::where('role', 'productor')->count();
                $data['totalAcopiadores'] = User::where('role', 'acopiador')->count();
                $data['solicitudesZonaPendientes'] = ZoneChangeRequest::with(['producer.zone', 'requestedZone', 'currentZone'])
                    ->where('status', 'pendiente')
                    ->latest()
                    ->get();
                break;
        }

        return view('dashboard', $data);
    }
}
