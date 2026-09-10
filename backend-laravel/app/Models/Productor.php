<?php

namespace App\Models;

use App\Enums\EstadoProductor;
use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Productor extends Model
{
    use Sincronizable;

    protected $table = 'productores';

    protected $fillable = [
        'usuario_id', 'codigo_padron', 'nombres', 'apellidos', 'dni',
        'zona_id', 'telefono', 'estado', 'fecha_ingreso', 'fecha_baja', 'motivo_baja',
    ];

    protected function casts(): array
    {
        return [
            'estado' => EstadoProductor::class,
            'fecha_ingreso' => 'date',
            'fecha_baja' => 'date',
        ];
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function zona(): BelongsTo
    {
        return $this->belongsTo(Zona::class, 'zona_id');
    }

    public function registrosAcopio(): HasMany
    {
        return $this->hasMany(RegistroAcopio::class, 'productor_id');
    }

    public function controlesCalidad(): HasMany
    {
        return $this->hasMany(ControlCalidad::class, 'productor_id');
    }

    public function sanciones(): HasMany
    {
        return $this->hasMany(Sancion::class, 'productor_id');
    }

    public function capacitaciones(): HasMany
    {
        return $this->hasMany(Capacitacion::class, 'productor_id');
    }

    public function scopeActivos($q)
    {
        return $q->where('estado', EstadoProductor::ACTIVO->value);
    }

    public function scopeDelPadron($q)
    {
        // "Padrón" = productores no expulsados ni retirados.
        return $q->whereIn('estado', [EstadoProductor::ACTIVO->value, EstadoProductor::SUSPENDIDO->value]);
    }

    /** ¿Tiene una sanción de agua vigente? Base de la reincidencia RN-05. */
    public function tieneSancionAguaVigente(): bool
    {
        return $this->sanciones()
            ->where('estado', '!=', 'anulada')
            ->whereIn('tipo', ['advertencia_descuento', 'descuento_y_retiro', 'expulsion_tarifa_minima'])
            ->exists();
    }
}
