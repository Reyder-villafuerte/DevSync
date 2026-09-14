<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyCashClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'date',
        'closed_by',
        'total_cash',
        'total_milk_discount',
        'total_amount',
        'cheese_molds_quantity',
        'sales_count',
        'notes',
        'closed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'total_cash' => 'decimal:2',
        'total_milk_discount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'closed_at' => 'datetime',
    ];

    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'closure_id');
    }
}
