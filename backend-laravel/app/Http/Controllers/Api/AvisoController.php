<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\AvisoActivoResource;
use App\Models\Aviso;
use App\Models\AvisoVisto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Avisos para el pop-up del productor. "Activos" = ya publicados (fecha de
 * publicación pasada) y no expirados.
 */
class AvisoController extends ApiController
{
    public function activos(Request $request): AnonymousResourceCollection
    {
        $productor = $request->user()->productor;

        $avisos = Aviso::query()
            ->vigentes()
            ->when($productor, fn ($q) => $q->with(['vistos' => fn ($v) => $v->where('productor_id', $productor->id)]))
            ->orderByDesc('obligatorio')
            ->orderByDesc('fecha_publicacion')
            ->get();

        return AvisoActivoResource::collection($avisos);
    }

    public function marcarVisto(Request $request, Aviso $aviso): JsonResponse
    {
        $productor = $request->user()->productor;
        abort_unless($productor, 403, 'Solo productores confirman avisos.');

        AvisoVisto::firstOrCreate(
            ['aviso_id' => $aviso->id, 'productor_id' => $productor->id],
            ['visto_en' => now()],
        );

        return response()->json(['mensaje' => 'Aviso confirmado.']);
    }
}
