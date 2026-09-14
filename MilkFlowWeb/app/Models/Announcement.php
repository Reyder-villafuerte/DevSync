<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = [
        'client_uuid',
        'title',
        'message',
        'start_date',
        'end_date',
        'target_role',
        'target_user_id',
        'created_by',
        'is_active'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targetUser()
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function scopeActiveForUser($query, User $user)
    {
        $today = date('Y-m-d');
        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->where(function ($q) use ($user) {
                $q->whereNull('target_role')
                  ->orWhere('target_role', $user->role)
                  ->orWhere('target_user_id', $user->id);
            });
    }
}
