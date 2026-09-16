<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantReception extends Model
{
    protected $fillable = [
        'client_uuid',
        'collection_route_id',
        'verifier_id',
        'collector_declared_liters',
        'flowmeter_liters',
        'difference_liters',
        'verification_status',
        'observation',
        'verified_at'
    ];

    public function route()
    {
        return $this->belongsTo(CollectionRoute::class, 'collection_route_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verifier_id');
    }
}
