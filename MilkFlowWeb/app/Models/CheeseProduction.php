<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheeseProduction extends Model
{
    protected $fillable = [
        'client_uuid',
        'production_date',
        'supervisor_id',
        'cheese_molds_produced',
        'milk_liters_used',
        'batch_number',
        'status'
    ];

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
