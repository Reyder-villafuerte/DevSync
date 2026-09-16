<?php

namespace App\Enums;

enum UserStatus: string
{
    case PENDIENTE = 'PENDIENTE';
    case ACTIVO = 'ACTIVO';
    case RECHAZADO = 'RECHAZADO';
    case INACTIVO = 'INACTIVO';
}
