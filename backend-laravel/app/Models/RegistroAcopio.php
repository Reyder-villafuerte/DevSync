<?php

namespace App\Models;

use App\Enums\EstadoRecepcion;
use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistroAcopio extends Model
{
    use Sincronizable;

    protected $table = 'registros_acopio';

    // OJO: las columnas `recepcion_*` y `estado_recepcion` NO se listan aquí a
    // propósito. Son autoría del servidor (VerificacionRecepcionService); dejarlas
    // fuera de $fillable impide que un push del móvil las falsee.
    protected $fillable = [
        'ruta_acopio_id', 'productor_id', 'litros', 'hora_registro',
        'observacion', 'sospecha_adulteracion',
    ];

    protected function casts(): array
    {
        return [
            'litros' => 'decimal:2',
            'hora_registro' => 'datetime',
            'sospecha_adulteracion' => 'boolean',
            'estado_recepcion' => EstadoRecepcion::class,
            'litros_recibidos' => 'decimal:2',
            'litros_faltantes' => 'decimal:2',
            'recepcion_confirmada_en' => 'datetime',
        ];
    }

    public function rutaAcopio(): BelongsTo
    {
        return $this->belongsTo(RutaAcopio::class, 'ruta_acopio_id');
    }

    public function productor(): BelongsTo
    {
        return $this->belongsTo(Productor::class, 'productor_id');
    }

    public function confirmadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'recepcion_confirmada_por');
    }

    /** Registros de una semana de pago (jueves-miércoles). */
    public function scopeEnSemana(Builder $q, string $inicio, string $fin): Builder
    {
        return $q->whereBetween('hora_registro', [$inicio.' 00:00:00', $fin.' 23:59:59']);
    }

    /** Entregas que el jefe de producción todavía no ha verificado. */
    public function scopePendientesDeRecepcion(Builder $q): Builder
    {
        return $q->where('estado_recepcion', EstadoRecepcion::PENDIENTE->value);
    }
}
