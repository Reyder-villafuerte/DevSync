<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\CerrarJornadaRequest;
use App\Http\Resources\CierreJornadaResource;
use App\Models\RutaAcopio;
use App\Services\Jornada\CierreJornadaService;
use Illuminate\Http\Request;

class JornadaController extends ApiController
{
    public function __construct(private readonly CierreJornadaService $cierre) {}

    /** POST /api/jornadas/{jornada}/cerrar */
    public function cerrar(CerrarJornadaRequest $request, RutaAcopio $jornada): CierreJornadaResource
    {
        // Autorización fina: solo el acopiador dueño de la jornada (o admin) la cierra.
        $this->authorize('cerrar', $jornada);

        $resultado = $this->cierre->cerrar(
            jornada: $jornada,
            lote: $request->only(['registros', 'descarga', 'controlesCalidad']),
            usuario: $request->user(),
            dispositivo: $this->dispositivoActual($request),
        );

        return new CierreJornadaResource($resultado);
    }
}
