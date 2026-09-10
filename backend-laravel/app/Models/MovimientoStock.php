<?php

namespace App\Models;

use App\Enums\TipoMovimientoStock;
use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Evento inmutable del libro de stock (requisito técnico 3). Nunca se edita ni
 * se borra físicamente: una corrección es otro movimiento de ajuste.
 */
class MovimientoStock extends Model
{
    use Sincronizable;

    protected $table = 'movimientos_stock';

    protected $fillable = [
        'producto_id', 'tipo_movimiento', 'cantidad', 'origen_tipo', 'origen_id',
        'registrado_por', 'ocurrido_en', 'motivo',
    ];

    protected function casts(): array
    {
        return [
            'tipo_movimiento' => TipoMovimientoStock::class,
            'cantidad' => 'decimal:2',
            'ocurrido_en' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function origen(): MorphTo
    {
        return $this->morphTo('origen', 'origen_tipo', 'origen_id');
    }

    protected static function booted(): void
    {
        // Guardarraíl: bloquea updates de filas ya persistidas (append-only).
        static::updating(function (MovimientoStock $m) {
            // Solo se permite tocar columnas de sincronización (version, deleted).
            $prohibidos = array_diff(array_keys($m->getDirty()), ['version', 'deleted', 'updated_at']);
            if ($prohibidos !== []) {
                throw new \RuntimeException('movimientos_stock es append-only; use un movimiento de ajuste.');
            }
        });
    }
}
