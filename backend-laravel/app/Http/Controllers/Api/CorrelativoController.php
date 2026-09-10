<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\ReservarRangoRequest;
use App\Models\Correlativo;
use App\Services\Facturacion\CorrelativoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Reserva y liberación de rangos de correlativo para facturar offline.
 * El dispositivo se resuelve desde el token (no se acepta del body).
 */
class CorrelativoController extends ApiController
{
    public function __construct(private readonly CorrelativoService $correlativos) {}

    /** POST /api/correlativos/reservar */
    public function reservar(ReservarRangoRequest $request): JsonResponse
    {
        $dispositivo = $this->dispositivoActual($request);

        $correlativo = Correlativo::firstOrCreate(
            ['tipo_comprobante' => $request->input('tipoComprobante'), 'serie' => $request->input('serie')],
            ['numero_maximo_asignado' => 0],
        );

        $rango = $this->correlativos->reservarRango($correlativo, $dispositivo, $request->integer('tamano') ?: null);

        return response()->json([
            'rangoId' => $rango->id,
            'serie' => $correlativo->serie,
            'tipoComprobante' => $correlativo->tipo_comprobante,
            'numeroDesde' => $rango->numero_desde,
            'numeroHasta' => $rango->numero_hasta,
            'numeroSiguiente' => $rango->numero_siguiente,
        ], 201);
    }

    /** POST /api/correlativos/cerrar-dia — libera los rangos del día del dispositivo. */
    public function cerrarDia(Request $request): JsonResponse
    {
        $dispositivo = $this->dispositivoActual($request);
        $liberados = $this->correlativos->liberarRangosDelDia($dispositivo);

        return response()->json([
            'liberados' => $liberados->map(fn ($r) => [
                'rangoId' => $r->id,
                'numeroDesde' => $r->numero_desde,
                'numeroHasta' => $r->numero_hasta,
            ])->all(),
            'total' => $liberados->count(),
        ]);
    }
}
