<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Auth;
use App\Exceptions\ReglaNegocioException;
use App\Models\Zone;
use App\Models\ZoneChangeRequest;
use App\Models\User;
use App\Services\Zonas\ZonaService;

class ZoneController extends Controller
{
    public function __construct(private ZonaService $zonas)
    {
    }

    public function index()
    {
        $zones = Zone::with(['producers', 'routes'])->get();
        $pendingRequests = ZoneChangeRequest::with(['producer.zone', 'requestedZone', 'currentZone'])
            ->where('status', 'pendiente')
            ->latest()
            ->get();

        return view('zonas.index', compact('zones', 'pendingRequests'));
    }

    // Vista dedicada para gestión y aprobación de solicitudes de cambio de zona
    public function solicitudes(Request $request)
    {
        $status = $request->get('estado', 'todos');

        $query = ZoneChangeRequest::with(['producer.zone', 'requestedZone', 'currentZone', 'reviewer'])
            ->latest();

        if ($status !== 'todos') {
            $query->where('status', $status);
        }

        $solicitudes = $query->paginate(15)->appends($request->all());

        $totalPendientes = ZoneChangeRequest::where('status', 'pendiente')->count();
        $totalAprobadas = ZoneChangeRequest::where('status', 'aprobado')->count();
        $totalRechazadas = ZoneChangeRequest::where('status', 'rechazado')->count();

        return view('zonas.solicitudes', compact('solicitudes', 'status', 'totalPendientes', 'totalAprobadas', 'totalRechazadas'));
    }

    // Productor solicita cambio de zona
    public function requestChange(Request $request)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'requested_zone_id' => ['required', 'exists:zones,id', 'different:current_zone_id'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->zonas->solicitarCambio(
                $user,
                (int) $validated['requested_zone_id'],
                $validated['reason'] ?? null
            );
        } catch (ReglaNegocioException $e) {
            return back()->withErrors([$e->campo() => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Solicitud de cambio de zona enviada a administración.');
    }

    // Admin aprueba o rechaza solicitud
    public function reviewRequest(Request $request, ZoneChangeRequest $zoneChangeRequest)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:aprobado,rechazado'],
        ]);

        $this->zonas->revisar($zoneChangeRequest, Auth::user(), $validated['decision']);

        return back()->with('success', "Solicitud {$validated['decision']} exitosamente.");
    }
}
