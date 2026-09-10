<?php

namespace App\Models;

use App\Enums\TipoSancion;
use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sancion extends Model
{
    use Sincronizable;

    protected $table = 'sanciones';

    protected $fillable = [
        'productor_id', 'control_calidad_id', 'semana_pago_id', 'tipo',
        'porcentaje_descuento', 'monto_descuento', 'tarifa_degradada_litro', 'retira_del_padron',
        'expulsa', 'aplicada_en_liquidacion', 'estado', 'detalle',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoSancion::class,
            'porcentaje_descuento' => 'decimal:4',
            'monto_descuento' => 'decimal:2',
            'tarifa_degradada_litro' => 'decimal:4',
            'retira_del_padron' => 'boolean',
            'expulsa' => 'boolean',
            'aplicada_en_liquidacion' => 'boolean',
        ];
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }

    public function controlCalidad(): BelongsTo
    {
        return $this->belongsTo(ControlCalidad::class, 'control_calidad_id');
    }

    public function semanaPago(): BelongsTo
    {
        return $this->belongsTo(SemanaPago::class, 'semana_pago_id');
    }

    public function scopeVigentes($q)
    {
        return $q->where('estado', 'vigente');
    }

    public function scopePendientesDeLiquidar($q)
    {
        return $q->where('estado', 'vigente')->where('aplicada_en_liquidacion', false);
    }
}
