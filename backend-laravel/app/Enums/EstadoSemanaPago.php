<?php

namespace App\Enums;

enum EstadoSemanaPago: string
{
    case ABIERTA = 'abierta';         // jueves-miércoles en curso
    case EN_CALCULO = 'en_calculo';
    case LIQUIDADA = 'liquidada';     // viernes: montos calculados
    case PAGADA = 'pagada';           // sobres entregados
    case CERRADA = 'cerrada';
}
