<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechnicalVisit extends Model
{
    protected $fillable = [
        'client_uuid',
        'lactoscan_analysis_id',
        'producer_id',
        'inspector_id',
        'scheduled_date',
        'scheduled_time',
        'status', // programada, realizada, cancelada
        'reason',
        'resolution_report'
    ];

    public function analysis()
    {
        return $this->belongsTo(LactoscanAnalysis::class, 'lactoscan_analysis_id');
    }

    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }
}
