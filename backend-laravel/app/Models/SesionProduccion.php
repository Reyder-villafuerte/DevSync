<?php

namespace App\Models;

use App\Enums\EstadoSesionProduccion;
use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SesionProduccion extends Model
{
    use Sincronizable;

    protected $table = 'sesiones_produccion';

    protected $fillable = [
        'producto_id', 'jefe_produccion_id', 'lote_codigo', 'fecha',
        'litros_procesados', 'unidades_producidas', 'estado', 'observaciones',
        // rendimiento_* y cumple_rn08 los escribe RendimientoService al completar.
        'rendimiento_por_100l', 'cumple_rn08', 'rendimiento_detalle', 'completada_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'litros_procesados' => 'decimal:2',
            'unidades_producidas' => 'integer',
            'rendimiento_por_100l' => 'decimal:2',
            'cumple_rn08' => 'boolean',
            'estado' => EstadoSesionProduccion::class,
            'completada_en' => 'datetime',
        ];
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class, 'producto_id');
    }

    public function jefeProduccion(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'jefe_produccion_id');
    }

    public function movimientosStock(): MorphMany
    {
        return $this->morphMany(MovimientoStock::class, 'origen', 'origen_tipo', 'origen_id');
    }
}
