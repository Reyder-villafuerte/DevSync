<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollectionRoute extends Model
{
    protected $fillable = [
        'client_uuid',
        'date',
        'zone_id',
        'collector_id',
        'start_time',
        'status',
        'total_collected_liters'
    ];

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    public function records()
    {
        return $this->hasMany(CollectionRecord::class);
    }

    public function reception()
    {
        return $this->hasOne(PlantReception::class);
    }
}
