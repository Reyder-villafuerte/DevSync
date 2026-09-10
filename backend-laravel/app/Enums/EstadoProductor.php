<?php

namespace App\Enums;

enum EstadoProductor: string
{
    case ACTIVO = 'activo';
    case SUSPENDIDO = 'suspendido';       // p. ej. derivado a capacitación
    case RETIRADO = 'retirado';           // retiro definitivo del padrón (RN-05)
    case EXPULSADO = 'expulsado';         // expulsión inmediata (RN-05 >= 5%)

    public function permiteAcopio(): bool
    {
        return $this === self::ACTIVO || $this === self::SUSPENDIDO;
    }
}
