<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'dni', 'zone_id', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    public function collectionRecords()
    {
        return $this->hasMany(CollectionRecord::class, 'producer_id');
    }

    public function lactoscanAnalyses()
    {
        return $this->hasMany(LactoscanAnalysis::class, 'producer_id');
    }

    public function assignedRoutes()
    {
        return $this->hasMany(CollectionRoute::class, 'collector_id');
    }

    public function settlements()
    {
        return $this->hasMany(ProducerSettlement::class, 'producer_id');
    }

    public function deductions()
    {
        return $this->hasMany(ProducerDeduction::class, 'producer_id');
    }

    public function zoneChangeRequests()
    {
        return $this->hasMany(ZoneChangeRequest::class, 'producer_id');
    }
}
