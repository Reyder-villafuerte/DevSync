<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\Pagos\LiquidacionService;
use App\Services\Sync\SyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

/**
 * API de sincronización de la app móvil MilkFlowMovil.
 *
 * El dispositivo trabaja siempre contra su base local; esta API solo mueve
 * deltas hacia abajo y operaciones encoladas hacia arriba.
 */
class SyncController extends Controller
{
    public function __construct(private SyncService $sync)
    {
    }

    /**
     * Inicio de sesión móvil. Acepta DNI o correo: en el campo el acopiador
     * escribe su DNI, que es lo que recuerda.
     */
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_id' => ['nullable', 'string', 'max:100'],
        ]);

        $identificador = trim($datos['usuario']);

        $usuario = User::where('dni', $identificador)
            ->orWhere('email', $identificador)
            ->first();

        if (!$usuario || !Hash::check($datos['password'], $usuario->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        if (!$usuario->is_active) {
            return response()->json(['message' => 'Tu cuenta está desactivada. Comunícate con administración.'], 403);
        }

        $nombreToken = 'milkflow-movil-' . ($datos['device_id'] ?? 'sin-dispositivo');
        $usuario->tokens()->where('name', $nombreToken)->delete();

        return response()->json([
            'token' => $usuario->createToken($nombreToken)->plainTextToken,
            'usuario' => [
                'id' => $usuario->id,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'dni' => $usuario->dni,
                'phone' => $usuario->phone,
                'role' => $usuario->role,
                'zone_id' => $usuario->zone_id,
            ],
            'avisos' => Announcement::activeForUser($usuario)->get(),
            'servidor_en' => now()->toIso8601String(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    /** Bajada incremental: deltas por entidad desde el cursor del dispositivo. */
    public function pull(Request $request): JsonResponse
    {
        $cursores = $request->input('cursores', []);
        if (is_string($cursores)) {
            $cursores = json_decode($cursores, true) ?: [];
        }

        $entidades = $request->input('entidades', []);
        if (is_string($entidades)) {
            $entidades = array_filter(explode(',', $entidades));
        }

        return response()->json(
            $this->sync->pull($request->user(), $cursores, $entidades)
        );
    }

    /** Subida de la cola de operaciones registradas sin señal. */
    public function push(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'device_id' => ['nullable', 'string', 'max:100'],
            'operaciones' => ['required', 'array', 'min:1'],
            'operaciones.*.client_uuid' => ['required', 'uuid'],
            'operaciones.*.comando' => ['required', 'string', 'max:60'],
            'operaciones.*.payload' => ['nullable', 'array'],
        ]);

        return response()->json([
            'resultados' => $this->sync->push($request->user(), $datos['device_id'] ?? null, $datos['operaciones']),
            'servidor_en' => now()->toIso8601String(),
        ]);
    }

    /**
     * Cálculo del sobre del ciclo abierto de un productor.
     * El móvil lo replica localmente; este endpoint es la referencia autoritativa.
     */
    public function ciclosPago(Request $request, LiquidacionService $liquidaciones): JsonResponse
    {
        $usuario = $request->user();

        $productores = in_array($usuario->role, ['personal_pago', 'pagador_campo', 'admin', 'jefe_general'], true)
            ? User::where('role', 'productor')->where('is_active', true)->orderBy('name')->get()
            : User::where('id', $usuario->id)->get();

        $ciclos = $productores->map(function (User $productor) use ($liquidaciones) {
            $ciclo = $liquidaciones->calcularCiclo($productor);

            return [
                'producer_id' => $productor->id,
                'start_date' => $ciclo['start_date'],
                'end_date' => $ciclo['end_date'],
                'liters' => $ciclo['liters'],
                'base_price' => $ciclo['base_price'],
                'effective_price' => $ciclo['effective_price'],
                'gross_base' => $ciclo['gross_base'],
                'cheese_deductions_total' => $ciclo['cheese_deductions_total'],
                'water_penalty_total' => $ciclo['water_penalty_total'],
                'total_deductions' => $ciclo['total_deductions'],
                'net' => $ciclo['net'],
                'adulteration_found' => $ciclo['adulteration_found'],
                'authorized' => (bool) $liquidaciones->sobreAutorizado($productor),
            ];
        });

        return response()->json(['ciclos' => $ciclos, 'servidor_en' => now()->toIso8601String()]);
    }
}
