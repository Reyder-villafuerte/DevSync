<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class PullRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            // Cursor: timestamp del último pull. Ausente => primera carga completa.
            'desde' => ['nullable', 'date'],
            // ruta:01 | zona:norte | productor:<uuid> | global
            'ambito' => ['nullable', 'string', 'regex:/^(ruta|zona|productor|global)(:[A-Za-z0-9_\-:]+)?$/'],
        ];
    }
}
