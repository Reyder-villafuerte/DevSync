<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\RegistrarInspeccionRequest;
use App\Http\Resources\DictamenCalidadResource;
use App\Enums\DictamenCalidad;
use App\Models\ControlCalidad;
use App\Services\Calidad\EvaluacionCalidadService;
use Illuminate\Support\Str;

/**
 * POST /api/inspecciones — registra un control de calidad y devuelve el
 * dictamen ya evaluado (RN-05 / RN-06). Idempotente por el UUID de cliente.
 */
class InspeccionController extends ApiController
{
    public function __construct(private readonly EvaluacionCalidadService $evaluacion) {}

    public function store(RegistrarInspeccionRequest $request): DictamenCalidadResource
    {
        $this->authorize('create', ControlCalidad::class);

        $dispositivo = $this->dispositivoActual($request);
        $id = $request->input('id') ?: (string) Str::uuid();

        // Upsert por UUID de cliente: reenviar la misma inspección no duplica.
        // No se usa updateOrCreate porque `id` no es fillable (se asigna con
        // forceFill para respetar el UUID generado en el cliente).
        $control = ControlCalidad::find($id) ?? tap(new ControlCalidad, fn ($c) => $c->forceFill(['id' => $id]));
        $control->fill([
            'productor_id' => $request->input('productorId'),
            'supervisor_id' => $request->user()->id,
            'ruta_acopio_id' => $request->input('rutaAcopioId'),
            'registro_acopio_id' => $request->input('registroAcopioId'),
            'dispositivo_id' => $dispositivo->id,
            'tomado_en' => $request->input('tomadoEn', now()),
            'tipo' => $request->input('tipo', 'inopinado'),
            'agua_anadida_porcentaje' => $request->input('aguaAnadidaPorcentaje'),
            'ph' => $request->input('ph'),
            'densidad' => $request->input('densidad'),
            'grasa_porcentaje' => $request->input('grasaPorcentaje'),
            'solidos_no_grasos_porcentaje' => $request->input('solidosNoGrasosPorcentaje'),
            'temperatura' => $request->input('temperatura'),
            'lactoscan_crudo' => $request->input('lactoscanCrudo'),
            'dictamen' => DictamenCalidad::APROBADO->value, // provisional; el Service lo fija
        ])->save();

        // La evaluación (dictamen + sanción/capacitación + estado del productor)
        // corre en su propia transacción dentro del Service.
        $control = $this->evaluacion->evaluar($control);

        return new DictamenCalidadResource($control);
    }
}
