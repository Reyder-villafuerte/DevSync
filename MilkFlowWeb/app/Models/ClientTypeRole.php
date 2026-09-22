<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un rol del padrón que se reconoce solo en un tipo de cliente. */
class ClientTypeRole extends Model
{
    protected $fillable = [
        'client_type_id',
        'role',
    ];

    public function clientType(): BelongsTo
    {
        return $this->belongsTo(ClientType::class);
    }

    public function etiqueta(): string
    {
        return config('huata.roles')[$this->role] ?? $this->role;
    }
}
