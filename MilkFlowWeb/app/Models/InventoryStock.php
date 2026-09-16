<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryStock extends Model
{
    protected $fillable = [
        'item_code',
        'item_name',
        'current_stock',
        'unit'
    ];

    public static function getStock(string $code): float
    {
        $item = static::where('item_code', $code)->first();
        return $item ? (float) $item->current_stock : 0.0;
    }

    public static function adjustStock(string $code, float $delta, string $name = '', string $unit = ''): static
    {
        $item = static::firstOrCreate(
            ['item_code' => $code],
            ['item_name' => $name ?: $code, 'current_stock' => 0, 'unit' => $unit ?: 'unidades']
        );
        $item->current_stock = max(0, (float) $item->current_stock + $delta);
        $item->save();
        return $item;
    }
}
