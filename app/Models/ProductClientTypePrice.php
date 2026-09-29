<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * La tarifa de UN producto para UN tipo de cliente.
 *
 * Cuando existe, manda sobre el estándar del tipo. Es lo que se llena en
 * Productos y Recetas al elegir a qué clientes se les da ese precio.
 */
class ProductClientTypePrice extends Model
{
    protected $fillable = [
        'product_id',
        'client_type_id',
        'price_per_unit',
    ];

    protected $casts = [
        'price_per_unit' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class);
    }
}
