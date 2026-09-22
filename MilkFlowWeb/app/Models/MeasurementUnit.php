<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Unidad de medida configurable: Litros (L), Gramos (g), Unidades (und)... */
class MeasurementUnit extends Model
{
    protected $fillable = [
        'name',
        'abbreviation',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function supplies(): HasMany
    {
        return $this->hasMany(Supply::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function label(): string
    {
        return "{$this->name} ({$this->abbreviation})";
    }
}
