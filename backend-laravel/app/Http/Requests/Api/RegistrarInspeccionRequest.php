<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta de un control de calidad (inspección) desde el móvil del supervisor.
 * Claves en camelCase; el Service las evalúa y persiste el dictamen.
 */
class RegistrarInspeccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'uuid'], // UUID de cliente para idempotencia
            'productorId' => ['required', 'uuid', 'exists:productores,id'],
            'rutaAcopioId' => ['nullable', 'uuid', 'exists:rutas_acopio,id'],
            'registroAcopioId' => ['nullable', 'uuid', 'exists:registros_acopio,id'],
            'tomadoEn' => ['nullable', 'date'],
            'tipo' => ['nullable', 'string', 'in:inopinado,rutina'],

            // Mediciones del Lactoscan. Al menos una de agua/pH para poder dictaminar.
            'aguaAnadidaPorcentaje' => ['nullable', 'numeric', 'min:0', 'max:100', 'required_without:ph'],
            'ph' => ['nullable', 'numeric', 'min:0', 'max:14', 'required_without:aguaAnadidaPorcentaje'],
            'densidad' => ['nullable', 'numeric'],
            'grasaPorcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'solidosNoGrasosPorcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'temperatura' => ['nullable', 'numeric'],
            'lactoscanCrudo' => ['nullable', 'array'],
        ];
    }
}
