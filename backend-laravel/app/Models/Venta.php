<?php

namespace App\Models;

use App\Support\Concerns\Sincronizable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use Sincronizable;

    protected $table = 'ventas';

    protected $fillable = [
        'cliente_id', 'registrado_por', 'rango_correlativo_id', 'tipo_comprobante',
        'serie_comprobante', 'numero_comprobante', 'fecha', 'total', 'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'total' => 'decimal:2',
            'numero_comprobante' => 'integer',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class, 'venta_id');
    }

    public function comprobante(): string
    {
        return sprintf('%s-%08d', $this->serie_comprobante, $this->numero_comprobante);
    }
}
