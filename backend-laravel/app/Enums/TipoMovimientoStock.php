<?php

namespace App\Enums;

// El signo de la cantidad lo define el tipo. Ingreso => +, egreso => -.
enum TipoMovimientoStock: string
{
    case PRODUCCION_INGRESO = 'produccion_ingreso';
    case VENTA_EGRESO = 'venta_egreso';
    case AJUSTE_POSITIVO = 'ajuste_positivo';
    case AJUSTE_NEGATIVO = 'ajuste_negativo';
    case MERMA = 'merma';

    public function signo(): int
    {
        return match ($this) {
            self::PRODUCCION_INGRESO, self::AJUSTE_POSITIVO => 1,
            self::VENTA_EGRESO, self::AJUSTE_NEGATIVO, self::MERMA => -1,
        };
    }
}
