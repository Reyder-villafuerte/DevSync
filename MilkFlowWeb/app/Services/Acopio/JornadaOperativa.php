<?php

namespace App\Services\Acopio;

use Carbon\CarbonImmutable;

class JornadaOperativa
{
    public function fecha(): string
    {
        $ahora = CarbonImmutable::now('America/Lima');

        return $ahora->lt($ahora->setTime(4, 30))
            ? $ahora->subDay()->toDateString()
            : $ahora->toDateString();
    }

    public function hora(): string
    {
        return CarbonImmutable::now('America/Lima')->format('H:i:s');
    }

    /**
     * Desde qué instante y hasta cuál corre esa jornada.
     *
     * La jornada no es el día del calendario: empieza a las 4:30 y termina a
     * las 4:30 del día siguiente. Un registro con hora —una venta, un cierre—
     * hay que buscarlo por esta ventana y no por `whereDate`, porque entre la
     * medianoche y las 4:30 la fecha del reloj ya cambió y la de la jornada
     * todavía no.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable} inicio incluido, fin excluido
     */
    public function ventana(?string $fecha = null): array
    {
        $inicio = CarbonImmutable::parse($fecha ?? $this->fecha(), 'America/Lima')->setTime(4, 30);

        return [$inicio, $inicio->addDay()];
    }
}
