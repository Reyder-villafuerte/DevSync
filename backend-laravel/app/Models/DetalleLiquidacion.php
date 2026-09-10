<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleLiquidacion extends Model
{
    use Sincronizable;

    protected $table = 'detalle_liquidacion';

    protected $fillable = [
        'liquidacion_id', 'concepto', 'descripcion', 'referencia_id', 'monto',
    ];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2'];
    }

    public function liquidacion(): BelongsTo
    {
        return $this->belongsTo(Liquidacion::class, 'liquidacion_id');
    }
}
