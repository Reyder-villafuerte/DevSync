<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class RutaAcopio extends Model
{
    use Sincronizable;

    protected $table = 'rutas_acopio';

    protected $fillable = [
        'acopiador_id', 'ruta_id', 'dispositivo_id', 'fecha',
        'hora_inicio', 'hora_cierre', 'litros_declarados', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'hora_inicio' => 'datetime',
            'hora_cierre' => 'datetime',
            'litros_declarados' => 'decimal:2',
        ];
    }

    public function acopiador(): BelongsTo
    {
        return $this->belongsTo(Acopiador::class, 'acopiador_id');
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class, 'ruta_id');
    }

    public function registros(): HasMany
    {
        return $this->hasMany(RegistroAcopio::class, 'ruta_acopio_id');
    }

    public function conciliacion(): HasOne
    {
        return $this->hasOne(Conciliacion::class, 'ruta_acopio_id');
    }
}
