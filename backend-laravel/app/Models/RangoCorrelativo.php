<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RangoCorrelativo extends Model
{
    use Sincronizable;

    protected $table = 'rangos_correlativo';

    protected $fillable = [
        'correlativo_id', 'dispositivo_id', 'numero_desde', 'numero_hasta',
        'numero_siguiente', 'agotado', 'reservado_en',
    ];

    protected function casts(): array
    {
        return [
            'numero_desde' => 'integer',
            'numero_hasta' => 'integer',
            'numero_siguiente' => 'integer',
            'agotado' => 'boolean',
            'reservado_en' => 'datetime',
        ];
    }

    public function correlativo(): BelongsTo
    {
        return $this->belongsTo(Correlativo::class, 'correlativo_id');
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class, 'dispositivo_id');
    }

    public function disponibles(): int
    {
        return $this->agotado ? 0 : ($this->numero_hasta - $this->numero_siguiente + 1);
    }
}
