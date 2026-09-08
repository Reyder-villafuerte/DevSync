<?php

namespace App\Enums;

enum EstadoSincronizacion: string
{
    case PENDIENTE = 'PENDIENTE';
    case ENVIADO = 'ENVIADO';
    case ERROR = 'ERROR';
}
