<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'sold_at'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function closure()
    {
        return $this->belongsTo(DailyCashClosure::class, 'closure_id');
    }

    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $count = static::whereYear('created_at', $year)->count() + 1;
        return sprintf('REC-%s-%05d', $year, $count);
    }
}
