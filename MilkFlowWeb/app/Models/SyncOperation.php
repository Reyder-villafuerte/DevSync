<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Bitácora de operaciones subidas por la app móvil.
 * Su client_uuid único es lo que hace idempotente el reenvío tras un corte de red.
 */
class SyncOperation extends Model
{
    protected $fillable = [
        'client_uuid',
        'user_id',
        'device_id',
        'command',
        'payload',
        'result',
        'status',
        'error_message',
        'applied_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
        'applied_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
