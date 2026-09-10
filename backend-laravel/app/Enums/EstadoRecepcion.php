<?php

namespace App\Enums;

/**
 * Estado de la verificación de recepción en planta de una entrega concreta
 * (un `registros_acopio`). Lo fija el jefe de producción al recibir la ruta.
 *
 * Se PERSISTE junto con los litros confirmados y no se recalcula al leer: la
 * tolerancia vigente al momento de recibir podría cambiar (mismo criterio que
 * el dictamen de calidad y la conciliación por caudalímetro).
 */
enum EstadoRecepcion: string
{
    case PENDIENTE = 'pendiente';   // el jefe aún no revisa esta entrega
    case CONFORME = 'conforme';     // recibió lo que el acopiador declaró (± tolerancia)
    case FALTANTE = 'faltante';     // el medidor de planta dio menos: faltó leche
    case EXCEDENTE = 'excedente';   // el medidor de planta dio más de lo declarado

    public function etiqueta(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::CONFORME => 'Conforme',
            self::FALTANTE => 'Faltante',
            self::EXCEDENTE => 'Excedente',
        };
    }

    public function verificado(): bool
    {
        return $this !== self::PENDIENTE;
    }
}
