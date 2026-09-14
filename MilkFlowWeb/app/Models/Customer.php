<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'client_uuid',
        'first_name',
        'last_name',
        'dni_ruc',
        'phone',
        'type', // proveedor, mayorista, local
        'linked_user_id',
        'is_wholesale_approved'
    ];

    public function linkedUser()
    {
        return $this->belongsTo(User::class, 'linked_user_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Calcula precio unitario según tarifas dinámicas configuradas por temporada:
     * - Proveedor: price_cheese_provider
     * - Mayorista aprobado o cantidad >= 10: price_cheese_wholesale
     * - Cliente local/regular: price_cheese_local
     */
    public function determineUnitPrice(int $quantity): float
    {
        return SystemPrice::getCheesePriceForCustomer($this, $quantity);
    }
}
