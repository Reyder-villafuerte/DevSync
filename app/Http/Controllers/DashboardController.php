<?php

namespace App\Http\Controllers;

use App\Models\CollectionRecord;
use App\Models\CollectionRoute;
use App\Models\InventoryStock;
use App\Models\LactoscanAnalysis;
use App\Models\Sale;
use App\Models\User;
use App\Models\Zone;
use App\Models\ZoneChangeRequest;
use App\Services\Acopio\JornadaOperativa;
use Illuminate\Support\Facades\Auth;

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

        $today = app(JornadaOperativa::class)->fecha();

        $stockLeche = InventoryStock::getStock(config('huata.codigos.leche'));
        $stockQueso = InventoryStock::getStock(config('huata.codigos.queso'));

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
                break;

            case 'personal_venta':
                $data['ventasHoy'] = Sale::deLaJornada($today)->with('customer')->latest()->get();
                $data['totalVentasHoy'] = Sale::deLaJornada($today)->sum('total_amount');
                $data['quesosVendidosHoy'] = Sale::deLaJornada($today)->sum('cheese_molds_quantity');
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
                $data['litrosAcopiadosHoy'] = CollectionRecord::whereHas('route', fn ($q) => $q->where('date', $today))->sum('liters');
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
