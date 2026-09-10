<?php

namespace App\Support\Concerns;

use Illuminate\Support\Str;

/**
 * Clave primaria UUID generada en el cliente (requisito técnico 1).
 *
 * - El tipo de clave es string y no incrementa.
 * - Si el registro llega SIN id (creación desde el panel web) se genera aquí
 *   un UUID v4. Si llega CON id (viene del móvil por sincronización) se
 *   respeta tal cual: es la garantía de inserción offline sin colisiones.
 */
trait UsaUuid
{
    public static function bootUsaUuid(): void
    {
        static::creating(function ($model) {
            $key = $model->getKeyName();
            if (empty($model->{$key})) {
                $model->{$key} = (string) Str::uuid();
            }
        });
    }

    public function initializeUsaUuid(): void
    {
        $this->keyType = 'string';
        $this->incrementing = false;
    }
}
