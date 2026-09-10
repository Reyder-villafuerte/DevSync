<?php

namespace App\Models;

use App\Enums\EstadoSemanaPago;
use App\Support\Concerns\Sincronizable;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SemanaPago extends Model
{
    use Sincronizable;

    protected $table = 'semanas_pago';

    protected $fillable = [
        'fecha_inicio', 'fecha_fin', 'fecha_liquidacion', 'estado',
        'precio_compra_leche_id', 'liquidada_en',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_liquidacion' => 'date',
            'estado' => EstadoSemanaPago::class,
            'liquidada_en' => 'datetime',
        ];
    }

    public function liquidaciones(): HasMany
    {
        return $this->hasMany(Liquidacion::class, 'semana_pago_id');
    }

    /**
     * Construye la semana de pago que contiene $fecha. El ciclo va de jueves
     * (Carbon::THURSDAY) a miércoles y se liquida el viernes siguiente.
     */
    public static function paraFecha(CarbonImmutable $fecha): array
    {
        $inicio = $fecha->startOfWeek(CarbonImmutable::THURSDAY);
        if ($fecha->lessThan($inicio)) {
            $inicio = $inicio->subWeek();
        }
        $fin = $inicio->addDays(6);              // miércoles
        $liquidacion = $fin->addDays(2);         // viernes

        return [
            'fecha_inicio' => $inicio->toDateString(),
            'fecha_fin' => $fin->toDateString(),
            'fecha_liquidacion' => $liquidacion->toDateString(),
        ];
    }
}
