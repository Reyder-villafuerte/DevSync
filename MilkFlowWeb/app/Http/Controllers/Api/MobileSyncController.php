<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\CollectionRoute;
use App\Models\CollectionRecord;
use App\Models\LactoscanAnalysis;
use App\Models\Announcement;
use Illuminate\Support\Facades\Hash;

class MobileSyncController extends Controller
{
    // Login para la app móvil: devuelve token Sanctum y anuncios activos
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }

        $token = $user->createToken('milkflow-mobile-token')->plainTextToken;
        $announcements = Announcement::activeForUser($user)->get();

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'zone_id' => $user->zone_id,
            ],
            'announcements' => $announcements,
        ]);
    }

    // Acopiador descarga datos para trabajar offline en ruta (4:30 AM)
    public function getCollectorRoute(Request $request)
    {
        $user = $request->user();
        $today = date('Y-m-d');

        $route = CollectionRoute::where('collector_id', $user->id)
            ->where('date', $today)
            ->with(['zone.producers', 'records'])
            ->first();

        if (!$route) {
            return response()->json(['message' => 'No tienes ruta asignada para hoy'], 404);
        }

        return response()->json([
            'route_id' => $route->id,
            'date' => $route->date,
            'zone' => [
                'id' => $route->zone->id,
                'name' => $route->zone->name,
                'code' => $route->zone->code,
            ],
            'status' => $route->status,
            'producers' => $route->zone->producers->map(function ($p) use ($route) {
                $record = $route->records->firstWhere('producer_id', $p->id);
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'dni' => $p->dni,
                    'phone' => $p->phone,
                    'collected_liters' => $record ? (float)$record->liters : null,
                ];
            }),
        ]);
    }

    // Acopiador sincroniza entregas recolectadas en lote (Batch Sync)
    public function syncDeliveries(Request $request)
    {
        $validated = $request->validate([
            'route_id' => 'required|exists:collection_routes,id',
            'deliveries' => 'required|array',
            'deliveries.*.producer_id' => 'required|exists:users,id',
            'deliveries.*.liters' => 'required|numeric|min:0.1',
            'deliveries.*.collected_at' => 'nullable|string',
            'deliveries.*.notes' => 'nullable|string',
        ]);

        $route = CollectionRoute::findOrFail($validated['route_id']);

        foreach ($validated['deliveries'] as $del) {
            CollectionRecord::updateOrCreate(
                ['collection_route_id' => $route->id, 'producer_id' => $del['producer_id']],
                [
                    'liters' => $del['liters'],
                    'collected_at' => $del['collected_at'] ?? date('H:i:s'),
                    'notes' => $del['notes'] ?? null,
                ]
            );
        }

        $route->total_collected_liters = $route->records()->sum('liters');
        $route->status = 'en_ruta';
        $route->save();

        return response()->json([
            'success' => true,
            'total_liters' => $route->total_collected_liters,
            'synced_count' => count($validated['deliveries']),
        ]);
    }

    // Productor consulta su historial desde la app móvil
    public function getProducerDeliveries(Request $request)
    {
        $user = $request->user();

        $deliveries = CollectionRecord::where('producer_id', $user->id)
            ->with('route.zone')
            ->latest()
            ->take(30)
            ->get();

        $analyses = LactoscanAnalysis::where('producer_id', $user->id)
            ->latest()
            ->take(10)
            ->get();

        return response()->json([
            'producer_name' => $user->name,
            'zone' => $user->zone ? $user->zone->name : null,
            'deliveries' => $deliveries,
            'lactoscan_analyses' => $analyses,
        ]);
    }
}
