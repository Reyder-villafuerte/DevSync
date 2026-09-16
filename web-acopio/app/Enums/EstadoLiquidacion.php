<?php

namespace App\Enums;

enum EstadoLiquidacion: string
{
    case Pendiente = 'Pendiente';
    case Calculada = 'Calculada';
    case Pagada = 'Pagada';
}
