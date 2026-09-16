<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_uuid',
        'category',
        'description',
        'amount',
        'expense_date',
        'user_id',
        'beneficiary_name',
        'payment_method',
        'receipt_number',
        'registered_by',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function staff()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function registrant()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
