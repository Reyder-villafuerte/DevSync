<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ReservarRangoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'tipoComprobante' => ['required', 'string', 'in:boleta,factura'],
            'serie' => ['required', 'string', 'max:10'],
            'tamano' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }
}
