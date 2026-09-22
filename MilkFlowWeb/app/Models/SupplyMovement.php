<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kardex del insumo: cada entrada o salida con su motivo y el saldo que dejó.
 *
 * Permite responder «¿en qué se fue la leche esta semana?» sin adivinar.
 */
class SupplyMovement extends Model
{
    public const TIPOS = ['ingreso', 'compra', 'anulacion_compra', 'consumo_produccion', 'devolucion', 'ajuste'];

    protected $fillable = [
        'supply_id',
        'type',
        'quantity',
        'unit_cost',
        'balance_after',
        'production_order_id',
        'purchase_id',
        'registered_by',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'balance_after' => 'decimal:4',
    ];

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class, 'production_order_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function etiqueta(): string
    {
        return match ($this->type) {
            'ingreso' => 'Ingreso',
            'compra' => 'Compra a proveedor',
            'anulacion_compra' => 'Anulación de compra',
            'consumo_produccion' => 'Consumo en producción',
            'devolucion' => 'Devolución de lote',
            default => 'Ajuste',
        };
    }
}
