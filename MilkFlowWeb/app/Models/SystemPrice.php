<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'season_name',
        'price_milk_base',
        'price_milk_water_penalty_low',
        'price_milk_water_penalty_high',
        'price_cheese_provider',
        'price_cheese_wholesale',
        'price_cheese_local',
        'is_active',
        'updated_by',
        'notes',
    ];

    protected $casts = [
        'price_milk_base' => 'decimal:2',
        'price_milk_water_penalty_low' => 'decimal:2',
        'price_milk_water_penalty_high' => 'decimal:2',
        'price_cheese_provider' => 'decimal:2',
        'price_cheese_wholesale' => 'decimal:2',
        'price_cheese_local' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtener los precios activos vigentes o crear registro por defecto
     */
    public static function current(): self
    {
        $current = static::where('is_active', true)->latest()->first();

        if (!$current) {
            $current = static::create([
                'season_name' => 'Temporada Regular Huata 2026',
                'price_milk_base' => 1.40,
                'price_milk_water_penalty_low' => 1.20,
                'price_milk_water_penalty_high' => 0.90,
                'price_cheese_provider' => 18.00,
                'price_cheese_wholesale' => 19.00,
                'price_cheese_local' => 20.00,
                'is_active' => true,
                'notes' => 'Tarifas iniciales configuradas por el sistema.',
            ]);
        }

        return $current;
    }

    /**
     * Calcular precio por litro de leche evaluando calidad/agua en Lactoscan
     * - Si agua detectada > 5%: S/ 0.90 (Penalidad grave / Expulsión)
     * - Si agua detectada > 0% y <= 5%: S/ 1.20 (Descuento por agua leve)
     * - Si normal: S/ 1.40 (Precio base)
     */
    public static function getMilkPriceForProducer($producerId, $startDate = null, $endDate = null): array
    {
        $prices = static::current();
        
        $query = LactoscanAnalysis::where('producer_id', $producerId);
        if ($startDate && $endDate) {
            $from = min($startDate, $endDate);
            $to = max($startDate, $endDate);
            $query->whereBetween('analysis_date', [$from, $to]);
        }
        $latestAnalysis = (clone $query)->orderByDesc('water_addition_percentage')->first();
        if (!$latestAnalysis) {
            $latestAnalysis = $query->latest('analysis_date')->first();
        }

        $waterPercentage = $latestAnalysis ? (float)$latestAnalysis->water_addition_percentage : 0.0;

        if ($waterPercentage > 5.0) {
            return [
                'price' => (float)$prices->price_milk_water_penalty_high,
                'penalty_type' => 'grave_expulsion',
                'water_percentage' => $waterPercentage,
                'reason' => "Agua adicionada alta ({$waterPercentage}%). Penalidad S/ {$prices->price_milk_water_penalty_high} y advertencia de expulsión.",
            ];
        } elseif ($waterPercentage > 0.0) {
            return [
                'price' => (float)$prices->price_milk_water_penalty_low,
                'penalty_type' => 'leve_descuento',
                'water_percentage' => $waterPercentage,
                'reason' => "Agua detectada ({$waterPercentage}% <= 5%). Penalidad reducida a S/ {$prices->price_milk_water_penalty_low}.",
            ];
        }

        return [
            'price' => (float)$prices->price_milk_base,
            'penalty_type' => 'ninguna',
            'water_percentage' => 0.0,
            'reason' => 'Leche conforme sin agua detectada.',
        ];
    }

    /**
     * Calcular precio unitario por molde de queso según tipo de cliente y cantidad
     */
    public static function getCheesePriceForCustomer($customer, int $quantity = 1): float
    {
        $prices = static::current();

        // 1. Proveedor / Productor de leche vinculado
        if ($customer && ($customer->type === 'proveedor' || $customer->linked_user_id)) {
            return (float)$prices->price_cheese_provider;
        }

        // 2. Mayorista o compra mayor o igual a 10 moldes
        if ($customer && ($customer->type === 'mayorista' || $customer->is_wholesale_approved || $quantity >= 10)) {
            return (float)$prices->price_cheese_wholesale;
        }

        // 3. Público general / Local
        return (float)$prices->price_cheese_local;
    }
}
