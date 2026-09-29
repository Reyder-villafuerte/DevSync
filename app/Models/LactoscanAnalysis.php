<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LactoscanAnalysis extends Model
{
    protected $fillable = [
        'client_uuid',
        'producer_id',
        'inspector_id',
        'analysis_date',
        'fat_percentage',
        'snf_percentage',
        'density',
        'protein_percentage',
        'water_addition_percentage',
        'temperature',
        'ph_or_acidity',
        'verdict', // conforme, acidez_alta, adulterada, sospechosa
        'notes'
    ];

    public function producer()
    {
        return $this->belongsTo(User::class, 'producer_id');
    }

    public function inspector()
    {
        return $this->belongsTo(User::class, 'inspector_id');
    }

    public function technicalVisits()
    {
        return $this->hasMany(TechnicalVisit::class);
    }
}
