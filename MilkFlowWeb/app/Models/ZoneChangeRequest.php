<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZoneChangeRequest extends Model
{
    protected $fillable = [
        'client_uuid',
        'producer_id',
        'current_zone_id',
        'requested_zone_id',
        'status', // pendiente, aprobado, rechazado
        'reason',
        'reviewed_by',
        'reviewed_at'
    ];

    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id');
    }

    public function currentZone()
    {
        return $this->belongsTo(Zone::class, 'current_zone_id');
    }

    public function requestedZone()
    {
        return $this->belongsTo(Zone::class, 'requested_zone_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
