<?php

namespace App\Services;

use App\Models as M;

class DisplayValue
{
    private array $labels = [];

    public function get($row, string $field): string
    {
        $value = $row->$field;
        $model = match ($field) {
            'productor_id' => M\Productor::class,'acopiador_id' => M\Acopiador::class,'zona_id','zona_nueva_id','zona_anterior_id' => M\Zona::class,'sector_id','sector_nuevo_id','sector_anterior_id' => M\Sector::class,'ruta_id' => M\Ruta::class,'usuario_id','user_id','created_by','updated_by','autor','solicitado_por','revisado_por' => M\User::class,default => null
        };
        if ($model && $value) {
            $key = $model.':'.$value;

            return $this->labels[$key] ??= ($model::find($value)?->{($model === M\User::class ? 'name' : 'nombre')} ?? '#'.$value);
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string) ($value ?? '—');
    }
}
