<?php

namespace App\Enums;

enum TipoCliente: string
{
    case Mayorista = 'Mayorista';
    case Socio = 'Productor / Socio';
    case Publico = 'Público General';

    public function precio(): int
    {
        return match ($this) {
            self::Mayorista => 20, self::Socio => 18, self::Publico => 21
        };
    }
}
