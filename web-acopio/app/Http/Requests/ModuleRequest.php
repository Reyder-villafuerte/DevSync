<?php

namespace App\Http\Requests;

use App\Services\Modules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission(Modules::get($this->route('module'))['permission']) ?? false;
    }

    public function rules(): array
    {
        $key = $this->route('module');
        $rules = [];
        foreach (Modules::get($key)['fields'] as $name => $field) {
            $rules[$name] = $field[2];
        }
        $id = $this->route('id');
        if ($key === 'producers') {
            $rules['documento'] = ['required', 'alpha_num', 'between:8,20', Rule::unique('productores', 'documento')->ignore($id)];
            $rules['user_id'] = ['nullable', Rule::exists('users', 'id')->where('status', 'ACTIVO'), Rule::unique('productores', 'user_id')->ignore($id)];
        }
        if ($key === 'acopiadores') {
            $rules['user_id'] = ['required', 'exists:users,id', Rule::unique('acopiadores', 'user_id')->ignore($id)];
        }
        if ($key === 'routes') {
            $rules['codigo'] = ['required', 'string', 'max:30', Rule::unique('rutas', 'codigo')->ignore($id)];
            $rules['sectores.*'] = 'required|distinct|exists:sectores,id';
        }
        if ($key === 'zones') {
            $rules['nombre'] = ['required', 'string', 'max:100', Rule::unique('zonas', 'nombre')->ignore($id)];
        }
        if ($key === 'sectors') {
            $rules['nombre'] = ['required', 'string', 'max:100', Rule::unique('sectores', 'nombre')->where('zona_id', $this->zona_id)->ignore($id)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return ['required' => 'El campo :attribute es obligatorio.', 'required_if' => 'El campo :attribute es obligatorio para esta opción.', 'unique' => 'Este valor ya está registrado.', 'exists' => 'La selección de :attribute no es válida.', 'numeric' => 'El campo :attribute debe ser numérico.', 'gt' => 'El campo :attribute debe ser mayor que :value.', 'min' => 'El campo :attribute no cumple el mínimo de :min.', 'max' => 'El campo :attribute supera el máximo de :max.', 'between' => 'El campo :attribute debe estar entre :min y :max.', 'integer' => 'El campo :attribute debe ser un número entero.', 'uuid' => 'La referencia UUID no es válida.', 'date' => 'Ingresa una fecha válida.', 'after_or_equal' => 'La fecha no puede ser anterior a hoy.'];
    }
}
