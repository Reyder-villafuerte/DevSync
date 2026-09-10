<?php

namespace App\Services\Sync;

/**
 * Acumula el desenlace de un lote de subida. Clasifica cada operación en
 * aceptada / conflicto / rechazada, guardando el motivo y el estado del
 * servidor en los conflictos.
 */
class ResultadoPush
{
    /** @var list<array{id:string,entidad:string,version:int,resultado:string}> */
    public array $aceptadas = [];

    /** @var list<array{id:string,entidad:string,motivo:string,servidor:array}> */
    public array $conflictos = [];

    /** @var list<array{id:?string,entidad:?string,motivo:string}> */
    public array $rechazadas = [];

    public function aceptar(string $id, string $entidad, int $version, string $resultado): void
    {
        $this->aceptadas[] = compact('id', 'entidad', 'version', 'resultado');
    }

    public function conflicto(string $id, string $entidad, string $motivo, array $servidor): void
    {
        $this->conflictos[] = compact('id', 'entidad', 'motivo', 'servidor');
    }

    public function rechazar(?string $id, ?string $entidad, string $motivo): void
    {
        $this->rechazadas[] = compact('id', 'entidad', 'motivo');
    }

    public function hayIncidencias(): bool
    {
        return $this->conflictos !== [] || $this->rechazadas !== [];
    }

    public function resumen(): array
    {
        return [
            'aceptadas' => count($this->aceptadas),
            'conflictos' => count($this->conflictos),
            'rechazadas' => count($this->rechazadas),
        ];
    }
}
