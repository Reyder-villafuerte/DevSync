<?php

namespace App\Enums;

enum TipoSancion: string
{
    case ADVERTENCIA_DESCUENTO = 'advertencia_descuento';         // RN-05 <5% 1ra
    case DESCUENTO_Y_RETIRO = 'descuento_y_retiro';               // RN-05 <5% reincidente
    case EXPULSION_TARIFA_MINIMA = 'expulsion_tarifa_minima';     // RN-05 >=5%
    case CAPACITACION_OBLIGATORIA = 'capacitacion_obligatoria';   // RN-06

    public function generaDescuento(): bool
    {
        return in_array($this, [
            self::ADVERTENCIA_DESCUENTO,
            self::DESCUENTO_Y_RETIRO,
            self::EXPULSION_TARIFA_MINIMA,
        ], true);
    }

    public function degradaTarifa(): bool
    {
        return $this === self::EXPULSION_TARIFA_MINIMA;
    }
}
