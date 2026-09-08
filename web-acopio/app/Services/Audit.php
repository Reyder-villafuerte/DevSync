<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Audit
{
    public static function record(
        string $action,
        Model $model,
        array $before = []
    ): void {
        $clean = static function ($data) {
            if (!is_array($data)) {
                return $data;
            }

            return array_diff_key(
                $data,
                array_flip([
                    'password',
                    'remember_token',
                    'token',
                ])
            );
        };

        $after = $model->getAttributes();

        if ($model instanceof User) {
            $after['roles'] = $model->roles()
                ->pluck('name')
                ->all();
        }

        Auditoria::create([
            'usuario_id' => Auth::id(),
            'accion' => $action,
            'modelo' => $model->getTable(),
            'registro' => $model->getKey(),
            'datos_anteriores' => $clean($before),
            'datos_nuevos' => $clean($after),
            'fecha' => now(),
        ]);
    }
}
