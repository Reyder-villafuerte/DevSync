<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'dni' => ['required', 'string', 'regex:/^[0-9]{8}$/'],
            'password' => ['required', 'string'],
            'dispositivo.identificador' => ['required', 'string', 'max:255'],
            'dispositivo.nombre' => ['nullable', 'string', 'max:255'],
            'dispositivo.plataforma' => ['nullable', 'string', 'in:android,ios'],
        ];
    }
}
