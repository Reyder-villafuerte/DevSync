<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Correlativo extends Model
{
    use Sincronizable;

    protected $table = 'correlativos';

    protected $fillable = [
        'tipo_comprobante', 'serie', 'numero_maximo_asignado', 'tamano_bloque_default',
    ];

    protected function casts(): array
    {
        return [
            'numero_maximo_asignado' => 'integer',
            'tamano_bloque_default' => 'integer',
        ];
    }

    public function rangos(): HasMany
    {
        return $this->hasMany(RangoCorrelativo::class, 'correlativo_id');
    }
}
