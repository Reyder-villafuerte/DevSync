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
}
