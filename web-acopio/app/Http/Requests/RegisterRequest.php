<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->email))]);
    }

    public function rules(): array
    {
        return ['nombres' => 'required|string|max:100', 'apellidos' => 'required|string|max:100', 'documento' => 'required|alpha_num|between:8,20|unique:users,documento', 'telefono' => ['required', 'regex:/^\+?[0-9 ()-]{9,20}$/'], 'email' => 'required|email|max:190|unique:users,email', 'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()]];
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'unique' => 'El :attribute ya está registrado.', 'email' => 'Ingresa un correo válido.', 'confirmed' => 'Las contraseñas no coinciden.', 'regex' => 'Ingresa un teléfono válido.', 'between' => 'El documento debe tener entre 8 y 20 caracteres.'];
    }
}
