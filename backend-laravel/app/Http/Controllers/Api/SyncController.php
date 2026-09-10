<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\PullRequest;
use App\Http\Requests\Api\PushRequest;
use App\Http\Resources\PushResultadoResource;
use App\Http\Resources\SyncPullResource;
use App\Services\Sync\SyncService;
use Illuminate\Http\JsonResponse;

/**
 * Sincronización offline-first. El controlador no decide nada: valida, resuelve
 * el dispositivo desde el token y delega en SyncService.
 *
 *   GET  /api/sync/pull?desde=&ambito=
 *   POST /api/sync/push
 */
class SyncController extends ApiController
{
    public function __construct(private readonly SyncService $sync) {}

    public function pull(PullRequest $request): SyncPullResource
    {
        $resultado = $this->sync->pull(
            usuario: $request->user(),
            desde: $request->input('desde'),
            ambitoCadena: $request->input('ambito'),
            dispositivo: $this->dispositivoActual($request),
        );

        return new SyncPullResource($resultado);
    }

    public function push(PushRequest $request): JsonResponse
    {
        $resultado = $this->sync->push(
            usuario: $request->user(),
            operaciones: $request->input('operaciones'),
            dispositivo: $this->dispositivoActual($request),
        );

        // 200 si todo entró; 207 (Multi-Status) si hubo conflictos o rechazos.
        $codigo = $resultado->hayIncidencias() ? 207 : 200;

        return (new PushResultadoResource($resultado))->response()->setStatusCode($codigo);
    }
}
