<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Exceptions\ReglaNegocioException;
use App\Models\LactoscanAnalysis;
use App\Models\TechnicalVisit;
use App\Models\User;
use App\Models\Zone;
use App\Models\CollectionRoute;
use App\Models\CollectionRecord;
use App\Services\Calidad\CalidadService;

class QualityController extends Controller
{
    public function __construct(private CalidadService $calidad)
    {
    }

    public function index(Request $request)
    {
        $today = date('Y-m-d');

        // 1. Zonas y Productores enriquecidos con los registros del acopiador de hoy
        $zones = Zone::where('is_active', true)->orderBy('code')->get();

        // Obtener registros de acopio de hoy con su ruta
        $todayRouteIds = CollectionRoute::whereDate('date', $today)
            ->orWhere('date', $today)
            ->pluck('id');

        $todayRecords = CollectionRecord::whereIn('collection_route_id', $todayRouteIds)
            ->with('route')
            ->get()
            ->keyBy('producer_id');

        $producers = User::where('role', 'productor')
            ->with('zone')
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($todayRecords) {
                $rec = $todayRecords->get($p->id);
                $p->today_liters = $rec ? (float) $rec->liters : null;
                $p->today_zone_id = $rec ? $rec->route->zone_id : ($p->zone_id ?? null);
                return $p;
            });

        // 2. Filtros para el historial de análisis Lactoscan (Buscador y filtros de veredicto)
        $search = $request->get('search');
        $verdict = $request->get('verdict');
        $zoneId = $request->get('zone_id');

        $analysesQuery = LactoscanAnalysis::with(['producer.zone', 'inspector', 'technicalVisits'])
            ->latest('analysis_date');

        if ($search) {
            $analysesQuery->whereHas('producer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('dni', 'like', "%{$search}%");
            });
        }

        if ($verdict && in_array($verdict, ['conforme', 'acidez_alta', 'adulterada', 'sospechosa'])) {
            $analysesQuery->where('verdict', $verdict);
        }

        if ($zoneId) {
            $analysesQuery->whereHas('producer', function ($q) use ($zoneId) {
                $q->where('zone_id', $zoneId);
            });
        }

        $analyses = $analysesQuery->paginate(15)->appends($request->query());

        // Contadores para las píldoras de filtrado
        $counts = [
            'all' => LactoscanAnalysis::count(),
            'conforme' => LactoscanAnalysis::where('verdict', 'conforme')->count(),
            'acidez_alta' => LactoscanAnalysis::where('verdict', 'acidez_alta')->count(),
            'adulterada' => LactoscanAnalysis::where('verdict', 'adulterada')->count(),
            'sospechosa' => LactoscanAnalysis::where('verdict', 'sospechosa')->count(),
        ];

        // 3. Registro de Visitas y Citas Técnicas del Día
        $todayVisits = TechnicalVisit::with(['producer.zone', 'inspector', 'analysis'])
            ->whereDate('scheduled_date', $today)
            ->orderBy('scheduled_time')
            ->get();

        return view('calidad.index', compact(
            'analyses',
            'producers',
            'zones',
            'counts',
            'todayVisits',
            'search',
            'verdict',
            'zoneId'
        ));
    }

    public function storeAnalysis(Request $request)
    {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('calidad.index')->with('error', 'El administrador solo tiene permisos de visualización sobre el historial de reportes.');
        }

        $validated = $request->validate([
            'producer_id' => ['required', 'exists:users,id'],
            'analysis_date' => ['required', 'date'],
            'fat_percentage' => ['nullable', 'numeric'],
            'snf_percentage' => ['nullable', 'numeric'],
            'density' => ['nullable', 'numeric'],
            'protein_percentage' => ['nullable', 'numeric'],
            'water_addition_percentage' => ['nullable', 'numeric'],
            'temperature' => ['nullable', 'numeric'],
            'ph_or_acidity' => ['nullable', 'numeric'],
            'verdict' => ['required', 'in:conforme,acidez_alta,adulterada,sospechosa'],
            'notes' => ['nullable', 'string', 'max:500'],
            // Si requiere agendar cita técnica inmediata:
            'schedule_visit' => ['nullable', 'boolean'],
            'scheduled_date' => ['required_if:schedule_visit,1', 'nullable', 'date'],
            'scheduled_time' => ['nullable'],
            'visit_reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->calidad->registrarAnalisis(Auth::user(), $validated);
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return redirect()->route('calidad.index')->with('success', 'Análisis Lactoscan registrado y notificado al productor.');
    }

    public function scheduleVisit(Request $request, LactoscanAnalysis $analysis)
    {
        $validated = $request->validate([
            'scheduled_date' => ['required', 'date'],
            'scheduled_time' => ['nullable'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $this->calidad->agendarVisita(
            $analysis,
            Auth::user(),
            $validated['scheduled_date'],
            $validated['scheduled_time'] ?? null,
            $validated['reason']
        );

        return back()->with('success', 'Cita de visita técnica agendada correctamente.');
    }

    public function completeVisit(Request $request, TechnicalVisit $visit)
    {
        $validated = $request->validate([
            'resolution_report' => ['required', 'string', 'max:1000'],
        ]);

        $this->calidad->completarVisita($visit, $validated['resolution_report']);

        return redirect()->route('calidad.index')->with('success', "Visita técnica al productor {$visit->producer->name} marcada como Realizada.");
    }
}
