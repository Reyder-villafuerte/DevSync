<?php

namespace App\Http\Controllers;

use App\Http\Requests\ModuleRequest;
use App\Models\Ruta;
use App\Models\SyncRecord;
use App\Services\DeliveryService;

class SyncController extends Controller
{
    public function index()
    {
        $records = SyncRecord::where('user_id', auth()->id())->latest()->paginate(20);

        return view('sync.index', compact('records'));
    }

    public function offline()
    {
        return response()->view('sync.offline', ['user' => auth()->user(), 'producers' => app(ModuleController::class)->query('producers')->where('activo', true)->get(['id', 'nombre']), 'routes' => Ruta::where('acopiador_id', auth()->user()->acopiador?->id ?? 0)->where('activo', true)->get(['id', 'nombre'])])->header('Cache-Control', 'private, no-store');
    }

    public function send(ModuleRequest $r, DeliveryService $service)
    {
        return response()->json(['data' => $service->create($r->validated(), $r->user())], 201);
    }
}
