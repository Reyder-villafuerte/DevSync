<?php

namespace App\Enums;

enum EstadoSesionProduccion: string
{
    case PLANIFICADA = 'planificada';
    case EN_PROCESO = 'en_proceso';
    case COMPLETADA = 'completada';   // dispara movimiento de stock
    case ANULADA = 'anulada';
}
