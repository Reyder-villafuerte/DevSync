<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Exceptions\ReglaNegocioException;
use App\Models\CollectionRoute;
use App\Models\Zone;
use App\Models\User;
use App\Services\Acopio\AcopioService;

class CollectionController extends Controller
{
    public function __construct(private AcopioService $acopio)
    {
    }

    // Mostrar u obtener ruta asignada
    public function index()
    {
        $user = Auth::user();
        $today = date('Y-m-d');

        if ($user->role === 'acopiador') {
            // Ruta del día: si no tiene, se auto-asigna una zona de Huata libre
            $route = $this->acopio->rutaDelDia($user, $today);

            // Historial de rutas de este acopiador con recepción en planta y observaciones
            $historicalRoutes = CollectionRoute::where('collector_id', $user->id)
                ->with(['zone', 'reception.verifier', 'records.producer'])
                ->orderByDesc('date')
                ->orderByDesc('id')
                ->take(30)
                ->get();

            if (!$route) {
                // Si todas las zonas de Huata ya están cubiertas hoy (turno de rotación / descanso)
                return view('acopio.sin_ruta', compact('today', 'historicalRoutes'));
            }

            $route->load(['zone.producers', 'records.producer', 'reception.verifier']);

            // Reordenamiento dinámico: proveedores pendientes arriba, ya acopiados abajo
            $recordedProducerIds = $route->records->pluck('producer_id')->flip()->toArray();
            $sortedProducers = $route->zone->producers->sortBy(function ($producer) use ($recordedProducerIds) {
                return isset($recordedProducerIds[$producer->id]) ? 1 : 0;
            })->values();
            $route->zone->setRelation('producers', $sortedProducers);

            return view('acopio.index', compact('route', 'historicalRoutes'));
        }

        // Para Admin / Jefe: lista de rutas del día
        $routes = CollectionRoute::where('date', $today)->with(['zone', 'collector', 'records'])->get();
        $zones = Zone::where('is_active', true)->get();
        $collectors = User::where('role', 'acopiador')->get();

        return view('acopio.admin', compact('routes', 'zones', 'collectors', 'today'));
    }

    // Registrar o actualizar entrega de un productor
    public function recordProducerDelivery(Request $request, CollectionRoute $route)
    {
        $validated = $request->validate([
            'producer_id' => ['required', 'exists:users,id'],
            'liters' => ['required', 'numeric', 'min:0.1'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $record = $this->acopio->registrarEntrega(
                $route,
                (int) $validated['producer_id'],
                (float) $validated['liters'],
                $validated['notes'] ?? null
            );
        } catch (ReglaNegocioException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'record' => $record,
                'total' => $route->fresh()->total_collected_liters,
            ]);
        }

        return back()->with('success', 'Entrega registrada correctamente.');
    }

    // Acopiador finaliza ruta y descarga en planta (espera caudalímetro)
    public function completeAndSendToPlant(CollectionRoute $route)
    {
        $this->acopio->cerrarRuta($route);

        return back()->with('success', 'Ruta descargada en planta. Pendiente de verificación por Jefe de Producción.');
    }

    // Admin asigna ruta a acopiador
    public function assignRoute(Request $request)
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'zone_id' => ['required', 'exists:zones,id'],
            'collector_id' => ['required', 'exists:users,id'],
            'start_time' => ['nullable'],
        ]);

        $this->acopio->asignarRuta(
            $validated['date'],
            (int) $validated['zone_id'],
            (int) $validated['collector_id'],
            $validated['start_time'] ?? null
        );

        return back()->with('success', 'Ruta asignada exitosamente.');
    }

    // Historial y Reportes de Acopio (Panel y Trazabilidad de Rutas)
    public function history(Request $request)
    {
        $user = Auth::user();

        $query = CollectionRoute::query()
            ->with(['zone', 'collector', 'reception.verifier', 'records.producer']);

        if ($user->role === 'acopiador') {
            $query->where('collector_id', $user->id);
        } elseif ($request->filled('collector_id')) {
            $query->where('collector_id', $request->collector_id);
        }

        // Filtro por zona si aplica
        if ($request->filled('zone_id')) {
            $query->where('zone_id', $request->zone_id);
        }

        // Filtro por período
        $period = $request->get('period', 'all');
        $today = date('Y-m-d');

        if ($period === 'today') {
            $query->where('date', $today);
        } elseif ($period === 'week') {
            $startOfWeek = date('Y-m-d', strtotime('monday this week'));
            $endOfWeek = date('Y-m-d', strtotime('sunday this week'));
            $query->whereBetween('date', [$startOfWeek, $endOfWeek]);
        } elseif ($period === 'month') {
            $startOfMonth = date('Y-m-01');
            $endOfMonth = date('Y-m-t');
            $query->whereBetween('date', [$startOfMonth, $endOfMonth]);
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $routes = $query->orderByDesc('date')->orderByDesc('id')->get();

        // Métricas consolidadas
        $totalRoutes = $routes->count();
        $totalFieldLiters = $routes->sum('total_collected_liters');
        $verifiedRoutes = $routes->filter(fn($r) => $r->reception && $r->reception->flowmeter_liters !== null);
        $totalFlowmeterLiters = $verifiedRoutes->sum(fn($r) => (float)$r->reception->flowmeter_liters);
        $totalLossLiters = $verifiedRoutes->sum(function($r) {
            return (float)$r->reception->flowmeter_liters - (float)$r->total_collected_liters;
        });
        $observationsCount = $routes->filter(fn($r) => $r->reception && !empty($r->reception->observation))->count();

        $zones = Zone::where('is_active', true)->get();
        $collectors = User::where('role', 'acopiador')->get();

        return view('acopio.historial', compact(
            'routes',
            'totalRoutes',
            'totalFieldLiters',
            'totalFlowmeterLiters',
            'totalLossLiters',
            'observationsCount',
            'period',
            'zones',
            'collectors'
        ));
    }
}
