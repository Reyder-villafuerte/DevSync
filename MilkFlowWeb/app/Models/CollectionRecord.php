<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionRecord extends Model
{
    protected $fillable = [
        'client_uuid',
        'collection_route_id',
        'producer_id',
        'liters',
        'collected_at',
        'notes'
    ];

    public function route()
    {
        return $this->belongsTo(CollectionRoute::class, 'collection_route_id');
    }

    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id');
    }
}
