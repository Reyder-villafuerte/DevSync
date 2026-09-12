<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class CerrarJornadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'registros' => ['array'],
            'registros.*.id' => ['required', 'uuid'],
            'registros.*.atributos' => ['required', 'array'],
            'registros.*.atributos.productorId' => ['required', 'uuid'],
            'registros.*.atributos.litros' => ['required', 'numeric', 'gt:0'],
            'registros.*.atributos.horaRegistro' => ['required', 'date'],

            'controlesCalidad' => ['array'],
            'controlesCalidad.*.id' => ['required', 'uuid'],
            'controlesCalidad.*.atributos.productorId' => ['required', 'uuid'],
        ];
    }
}
