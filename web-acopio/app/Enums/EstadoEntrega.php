<?php

namespace App\Enums;

enum EstadoEntrega: string
{
    case PENDIENTE = 'PENDIENTE';
    case CONFORME = 'CONFORME';
    case OBSERVADA = 'OBSERVADA';
    case RECHAZADA = 'RECHAZADA';
}
