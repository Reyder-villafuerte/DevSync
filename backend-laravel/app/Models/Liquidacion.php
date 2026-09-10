<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Liquidacion extends Model
{
    use Sincronizable;

    protected $table = 'liquidaciones';

    protected $fillable = [
        'semana_pago_id', 'productor_id', 'litros_totales', 'precio_litro_aplicado',
        'tarifa_degradada', 'monto_bruto', 'total_descuentos', 'monto_neto',
        'estado', 'sobre_entregado_en',
    ];

    protected function casts(): array
    {
        return [
            'litros_totales' => 'decimal:2',
            'precio_litro_aplicado' => 'decimal:4',
            'tarifa_degradada' => 'boolean',
            'monto_bruto' => 'decimal:2',
            'total_descuentos' => 'decimal:2',
            'monto_neto' => 'decimal:2',
            'sobre_entregado_en' => 'datetime',
        ];
    }

    public function semanaPago(): BelongsTo
    {
        return $this->belongsTo(SemanaPago::class, 'semana_pago_id');
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleLiquidacion::class, 'liquidacion_id');
    }
}
