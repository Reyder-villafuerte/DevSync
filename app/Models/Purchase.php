<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Compra de insumos: cabecera con proveedor, fecha y documento; el detalle vive
 * en `items`. Al guardarse mueve el almacén, así que no hay borradores.
 */
class Purchase extends Model
{
    protected $fillable = [
        'supplier_id',
        'purchase_date',
        'document_number',
        'total_amount',
        'notes',
        'registered_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(SupplyMovement::class);
    }

    public function registrar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    /** Cómo se nombra esta compra en el kardex y en los mensajes de error. */
    public function label(): string
    {
        return $this->document_number
            ? "compra {$this->document_number}"
            : "compra #{$this->id}";
    }
}
