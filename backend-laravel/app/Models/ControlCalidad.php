<?php

namespace App\Models;

use App\Enums\DictamenCalidad;
use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ControlCalidad extends Model
{
    use Sincronizable;

    protected $table = 'controles_calidad';

    protected $fillable = [
        'productor_id', 'supervisor_id', 'ruta_acopio_id', 'registro_acopio_id', 'dispositivo_id',
        'tomado_en', 'tipo',
        'agua_anadida_porcentaje', 'ph', 'densidad', 'grasa_porcentaje',
        'solidos_no_grasos_porcentaje', 'temperatura', 'lactoscan_crudo',
        // El dictamen se asigna vía el Service, pero se permite en fillable
        // para que la sincronización de subida pueda traerlo ya evaluado
        // desde el móvil y el servidor solo lo revalide.
        'dictamen', 'dictamen_detalle', 'rechaza_lote', 'es_reincidencia', 'umbral_agua_aplicado',
    ];

    protected function casts(): array
    {
        return [
            'tomado_en' => 'datetime',
            'agua_anadida_porcentaje' => 'decimal:2',
            'ph' => 'decimal:2',
            'densidad' => 'decimal:3',
            'grasa_porcentaje' => 'decimal:2',
            'solidos_no_grasos_porcentaje' => 'decimal:2',
            'temperatura' => 'decimal:2',
            'lactoscan_crudo' => 'array',
            'dictamen' => DictamenCalidad::class,
            'rechaza_lote' => 'boolean',
            'es_reincidencia' => 'boolean',
        ];
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'supervisor_id');
    }

    public function sancion(): HasOne
    {
        return $this->hasOne(Sancion::class, 'control_calidad_id');
    }

    public function capacitacion(): HasOne
    {
        return $this->hasOne(Capacitacion::class, 'control_calidad_id');
    }
}
