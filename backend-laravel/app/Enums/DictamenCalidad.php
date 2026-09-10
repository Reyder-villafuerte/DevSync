<?php

namespace App\Enums;

// Resultado del control de calidad. Se PERSISTE junto a las mediciones y no
// se recalcula al leer (requisito técnico 5).
enum DictamenCalidad: string
{
    case APROBADO = 'aprobado';
    case RECHAZADO_ACIDEZ = 'rechazado_acidez';               // RN-06: pH < 6.5
    case ADVERTENCIA_AGUA = 'advertencia_agua';               // RN-05: <5%, 1ra vez
    case DESCUENTO_RETIRO_AGUA = 'descuento_retiro_agua';     // RN-05: <5%, reincidencia
    case EXPULSION_AGUA = 'expulsion_agua';                   // RN-05: >=5%

    public function rechazaLote(): bool
    {
        return $this === self::RECHAZADO_ACIDEZ || $this === self::EXPULSION_AGUA;
    }
}
