<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Una tarifa de acopio con su condición.
 *
 * Sin `metric` es la tarifa base: lo que se paga cuando ninguna otra aplica.
 * Con métrica, la regla se activa si la medida de calidad del ciclo cumple la
 * comparación; entre varias que apliquen gana la de mayor `priority`.
 */
class CollectionPriceRule extends Model
{
    public const OPERADORES = ['>', '>=', '<', '<=', '='];

    protected $fillable = [
        'supply_id',
        'name',
        'metric',
        'operator',
        'threshold',
        'price_per_unit',
        'applies_to_whole_cycle',
        'penalty_type',
        'priority',
        'is_active',
    ];

    protected $casts = [
        'threshold' => 'decimal:2',
        'price_per_unit' => 'decimal:2',
        'applies_to_whole_cycle' => 'boolean',
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function esBase(): bool
    {
        return $this->metric === null;
    }

    /** ¿Esta regla aplica con las medidas de calidad del ciclo? */
    public function aplicaCon(array $metricas): bool
    {
        if ($this->esBase()) {
            return true;
        }

        if (! array_key_exists($this->metric, $metricas) || $metricas[$this->metric] === null) {
            return false;
        }

        $valor = (float) $metricas[$this->metric];
        $umbral = (float) $this->threshold;

        return match ($this->operator) {
            '>' => $valor > $umbral,
            '>=' => $valor >= $umbral,
            '<' => $valor < $umbral,
            '<=' => $valor <= $umbral,
            '=' => abs($valor - $umbral) < 0.0001,
            default => false,
        };
    }

    /** Cómo se le explica al productor por qué se le pagó esto. */
    public function motivo(array $metricas): string
    {
        if ($this->esBase()) {
            return 'Producto conforme: se paga la tarifa base.';
        }

        $valor = $metricas[$this->metric] ?? 0;
        $umbral = rtrim(rtrim(number_format((float) $this->threshold, 2, '.', ''), '0'), '.');

        return "{$this->name}: la medida dio {$valor} ({$this->operator} {$umbral}), "
            ."se paga S/ {$this->price_per_unit} por unidad.";
    }

    public function etiquetaCondicion(): string
    {
        if ($this->esBase()) {
            return 'Siempre (tarifa base)';
        }

        $umbral = rtrim(rtrim(number_format((float) $this->threshold, 2, '.', ''), '0'), '.');

        return "{$this->metric} {$this->operator} {$umbral}";
    }
}
