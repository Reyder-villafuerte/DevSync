<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conciliacion extends Model
{
    use Sincronizable;

    protected $table = 'conciliaciones';

    // diferencia_*, tiene_alerta las CALCULA ConciliacionService y las persiste;
    // ningún Form Request las expone a entrada HTTP.
    protected $fillable = [
        'ruta_acopio_id', 'registrado_por', 'litros_acopiador', 'litros_caudalimetro',
        'diferencia_litros', 'diferencia_porcentaje', 'tolerancia_aplicada_pct',
        'tiene_alerta', 'observacion', 'conciliado_en',
    ];

    protected function casts(): array
    {
        return [
            'litros_acopiador' => 'decimal:2',
            'litros_caudalimetro' => 'decimal:2',
            'diferencia_litros' => 'decimal:2',
            'diferencia_porcentaje' => 'decimal:3',
            'tolerancia_aplicada_pct' => 'decimal:2',
            'tiene_alerta' => 'boolean',
            'conciliado_en' => 'datetime',
        ];
    }

    public function rutaAcopio(): BelongsTo
    {
        return $this->belongsTo(RutaAcopio::class, 'ruta_acopio_id');
    }

    public function scopeConAlerta($q)
    {
        return $q->where('tiene_alerta', true);
    }
}
