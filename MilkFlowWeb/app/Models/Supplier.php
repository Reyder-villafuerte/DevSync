<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A quién la planta le compró envases, cuajo o fruta.
 *
 * No es un padrón que se mantenga a mano: la ficha nace de la boleta que se
 * sube en Compras y existe para que el historial pueda decir a quién se le
 * compró sin repetir el mismo nombre en cada compra. Si el de la boleta es un
 * poblador, eso no cambia cómo se le paga la leche: esa entra por acopio y se
 * cobra en la liquidación semanal.
 */
class Supplier extends Model
{
    protected $fillable = [
        'name',
        'document',
        'phone',
        'address',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function label(): string
    {
        return $this->document ? "{$this->name} ({$this->document})" : $this->name;
    }
}
