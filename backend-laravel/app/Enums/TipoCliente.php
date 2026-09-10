<?php

namespace App\Enums;

enum TipoCliente: string
{
    case MAYORISTA = 'mayorista';
    case SOCIO = 'socio';
    case PUBLICO = 'publico';
}
