<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProducerSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'settlement_code',
        'producer_id',
        'start_date',
        'end_date',
        'total_liters',
        'price_per_liter',
        'gross_total',
        'deductions_total',
        'net_total',
        'status',
        'paid_at',
        'payment_method',
        'paid_by',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'paid_at' => 'datetime',
        'total_liters' => 'decimal:2',
        'price_per_liter' => 'decimal:2',
        'gross_total' => 'decimal:2',
        'deductions_total' => 'decimal:2',
        'net_total' => 'decimal:2',
    ];

    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id');
    }

    public function payer()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function deductions()
    {
        return $this->hasMany(ProducerDeduction::class, 'settlement_id');
    }
}
