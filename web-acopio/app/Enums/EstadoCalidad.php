<?php

namespace App\Enums;

enum EstadoCalidad: string
{
    case CONFORME = 'CONFORME';
    case OBSERVADA = 'OBSERVADA';
    case RECHAZADA = 'RECHAZADA';
}
