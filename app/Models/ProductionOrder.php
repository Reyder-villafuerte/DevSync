<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Lote de producción. Nace planificado, se inicia descontando los insumos y
 * recién al cumplir su tiempo de proceso entrega el producto terminado.
 */
class ProductionOrder extends Model
{
    public const ESTADOS = ['planificada', 'en_proceso', 'terminada', 'cancelada'];

    protected $fillable = [
        'batch_number',
        'product_id',
        'supervisor_id',
        'planned_quantity',
        'produced_quantity',
        'status',
        'started_at',
        'expected_ready_at',
        'finished_at',
        'notes',
    ];

    protected $casts = [
        'planned_quantity' => 'decimal:2',
        'produced_quantity' => 'decimal:2',
        'started_at' => 'datetime',
        'expected_ready_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionOrderItem::class);
    }

    /** El proceso ya cumplió su tiempo y el lote puede cerrarse. */
    public function isReady(): bool
    {
        return $this->status === 'en_proceso'
            && (! $this->expected_ready_at || $this->expected_ready_at->isPast());
    }

    public function minutesRemaining(): int
    {
        if ($this->status !== 'en_proceso' || ! $this->expected_ready_at || $this->expected_ready_at->isPast()) {
            return 0;
        }

        return (int) ceil(now()->diffInSeconds($this->expected_ready_at, false) / 60);
    }
}
