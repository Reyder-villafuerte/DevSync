<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProducerDeduction extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'producer_id',
        'settlement_id',
        'sale_id',
        'date',
        'concept',
        'amount',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id');
    }

    public function settlement()
    {
        return $this->belongsTo(ProducerSettlement::class, 'settlement_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Obtiene la venta asociada ya sea por foreign key sale_id o extrayendo el número de recibo del concepto
     */
    public function getMatchedSaleAttribute()
    {
        if ($this->relationLoaded('sale') && $this->sale) {
            return $this->sale;
        }

        if ($this->sale_id) {
            return $this->sale;
        }

        if (preg_match('/#(REC-[\w\-]+)/', $this->concept, $matches)) {
            return Sale::where('receipt_number', $matches[1])->first();
        }

        return null;
    }
}

