<?php

namespace App\Services\Sync;

use App\Exceptions\ReglaNegocioException;

/**
 * Ámbito de sincronización pedido por el cliente: `ruta:01`, `zona:norte`,
 * `productor:<uuid>`. Objeto de valor inmutable.
 */
final class AmbitoSincronizacion
{
    private const TIPOS = ['ruta', 'zona', 'productor', 'global'];

    private function __construct(
        public readonly string $tipo,
        public readonly ?string $valor,
    ) {}

    public static function global(): self
    {
        return new self('global', null);
    }

    public static function de(string $tipo, string $valor): self
    {
        return new self($tipo, $valor);
    }

    /** Parsea "tipo:valor". Cadena vacía => ámbito global. */
    public static function desdeCadena(?string $cadena): self
    {
        if ($cadena === null || trim($cadena) === '' || $cadena === 'global') {
            return self::global();
        }

        if (! str_contains($cadena, ':')) {
            throw new ReglaNegocioException("Ámbito mal formado: '{$cadena}'. Formato esperado tipo:valor.", 'AMBITO_INVALIDO');
        }

        [$tipo, $valor] = explode(':', $cadena, 2);
        $tipo = strtolower(trim($tipo));

        if (! in_array($tipo, self::TIPOS, true)) {
            throw new ReglaNegocioException("Tipo de ámbito no soportado: '{$tipo}'.", 'AMBITO_INVALIDO');
        }

        return new self($tipo, trim($valor));
    }

    public function esGlobal(): bool
    {
        return $this->tipo === 'global';
    }

    public function __toString(): string
    {
        return $this->esGlobal() ? 'global' : "{$this->tipo}:{$this->valor}";
    }
}
