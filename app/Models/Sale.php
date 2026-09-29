<?php

namespace App\Models;

use App\Services\Acopio\JornadaOperativa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Venta de mostrador. El detalle vive en `items`: una venta puede llevar queso,
 * yogurt y lo que la planta produzca, cada renglón con su propia tarifa.
 *
 * `cheese_molds_quantity` se conserva por el contrato con la app móvil y por el
 * arqueo de caja, pero hoy significa «unidades vendidas en total».
 */
class Sale extends Model
{
    protected $fillable = [
        'client_uuid',
        'receipt_number',
        'customer_id',
        'seller_id',
        'closure_id',
        'cheese_molds_quantity',
        'unit_price',
        'total_amount',
        'payment_method',
        'sold_at',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function closure(): BelongsTo
    {
        return $this->belongsTo(DailyCashClosure::class, 'closure_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** Unidades despachadas, sumando todos los renglones. */
    public function unitsQuantity(): float
    {
        return (float) $this->cheese_molds_quantity;
    }

    /** Cómo se resume el contenido de la venta en una línea de tabla. */
    public function resumenItems(): string
    {
        $items = $this->relationLoaded('items') ? $this->items : $this->items()->with('product')->get();

        if ($items->isEmpty()) {
            return '—';
        }

        return $items->map(function (SaleItem $item) {
            $cantidad = rtrim(rtrim(number_format((float) $item->quantity, 2, '.', ''), '0'), '.');

            return "{$cantidad} × {$item->product->name}";
        })->implode(', ');
    }

    /**
     * Las ventas de una jornada operativa.
     *
     * `sold_at` lleva hora, y la jornada corre de 4:30 a 4:30. Filtrar por
     * `whereDate` pierde todo lo vendido entre la medianoche y las 4:30: para
     * el reloj ya es otro día, para la planta todavía no.
     */
    public function scopeDeLaJornada(Builder $query, ?string $fecha = null): Builder
    {
        [$inicio, $fin] = app(JornadaOperativa::class)->ventana($fecha);

        return $query->where('sold_at', '>=', $inicio)->where('sold_at', '<', $fin);
    }

    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;

        return sprintf('REC-%s-%05d', $year, $count);
    }
}
