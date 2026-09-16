<?php

namespace App\Http\Controllers;

use App\Actions\RegisterWorker;
use App\Http\Requests\ModuleRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Services\DeliveryService;
use App\Services\ProductionService;
use App\Services\QualityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    public function login(Request $r)
    {
        $data = $r->validate(['login' => 'required|string|max:190', 'password' => 'required|string', 'device_name' => 'nullable|string|max:100']);
        $u = User::where('email', mb_strtolower($data['login']))->orWhere('username', $data['login'])->first();
        if (! $u || ! Hash::check($data['password'], $u->password)) {
            return response()->json(['message' => 'Las credenciales no son correctas.'], 422);
        }
        if ($u->status !== 'ACTIVO' || $u->roles()->doesntExist()) {
            return response()->json(['message' => $u->status === 'PENDIENTE' ? 'Tu cuenta está pendiente de aprobación por el administrador.' : 'La cuenta no está activa.'], 403);
        }

        return response()->json(['token' => $u->createToken($data['device_name'] ?? 'Android', ['*'], now()->addDays(7))->plainTextToken, 'user' => $u->load('roles'), 'dashboard' => $u->dashboard()]);
    }

    public function register(RegisterRequest $r, RegisterWorker $action)
    {
        $u = $action->execute($r->validated());

        return response()->json(['message' => 'Solicitud registrada. Pendiente de aprobación.', 'status' => $u->status], 201);
    }

    public function logout(Request $r)
    {
        $r->user()->currentAccessToken()?->delete();

        return response()->noContent();
    }

    public function list(Request $r)
    {
        $module = $r->route('module');
        $q = app(ModuleController::class)->query($module);
        if ($module === 'producers' && $r->user()->hasRole('productor')) {
            abort(403);
        }

        return $q->orderByDesc('id')->paginate(50);
    }

    public function create(ModuleRequest $r)
    {
        $service = match ($r->route('module')) {
            'entregas' => DeliveryService::class,'calidad' => QualityService::class,'lotes' => ProductionService::class
        };
        $row = app($service)->create($r->validated(), $r->user());

        return response()->json(['data' => $row], 201);
    }

    public function notifications(Request $r)
    {
        return $r->user()->notifications()->paginate(50);
    }
}
