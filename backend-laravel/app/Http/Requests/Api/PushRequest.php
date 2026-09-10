<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $entidades = implode(',', array_keys(config('sync.entidades')));

        return [
            'operaciones' => ['required', 'array', 'min:1', 'max:'.config('sync.lote_push_max')],
            'operaciones.*.entidad' => ['required', 'string', 'in:'.$entidades],
            'operaciones.*.id' => ['required', 'uuid'],
            'operaciones.*.versionBase' => ['nullable', 'integer', 'min:0'],
            'operaciones.*.eliminar' => ['nullable', 'boolean'],
            // `atributos` es obligatorio salvo en operaciones de borrado; la
            // combinación exacta la valida SyncService por entidad.
            'operaciones.*.atributos' => ['nullable', 'array'],
        ];
    }
}
