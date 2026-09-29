<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Error de regla de negocio (stock insuficiente, pago no autorizado, etc.).
 *
 * Se distingue de un error técnico: el dispositivo móvil NO debe reintentar
 * la operación, porque el servidor la rechazó por su contenido, no por la red.
 */
class ReglaNegocioException extends RuntimeException
{
    public function __construct(string $message, private string $campo = 'general')
    {
        parent::__construct($message);
    }

    public function campo(): string
    {
        return $this->campo;
    }
}
