<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Violación de una regla de negocio de la planta (RN-05, RN-06, RN-08,
 * tolerancia volumétrica, etc.). Se lanza desde los Services y aborta la
 * transacción en curso. Se traduce a HTTP 422 automáticamente.
 */
class ReglaNegocioException extends RuntimeException
{
    public function __construct(
        string $mensaje,
        public readonly string $regla = 'REGLA_NEGOCIO',
        public readonly array $contexto = [],
    ) {
        parent::__construct($mensaje);
    }

    public function render($request)
    {
        return response()->json([
            'error' => $this->regla,
            'mensaje' => $this->getMessage(),
            'contexto' => $this->contexto,
        ], 422);
    }
}
