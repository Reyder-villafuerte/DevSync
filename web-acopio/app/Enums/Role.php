<?php

namespace App\Enums;

enum Role: string
{
    case Administrador = 'Administrador';
    case Acopiador = 'Acopiador';
    case Supervisor = 'Supervisor';
    case Produccion = 'Jefe de producción';
    case Despacho = 'Personal de despacho de queso';
    case Productor = 'Productor';

    public function slug(): string
    {
        return match ($this) {
            self::Administrador => 'admin', self::Acopiador => 'acopiador', self::Supervisor => 'supervisor', self::Produccion => 'produccion', self::Despacho => 'despacho', self::Productor => 'productor'
        };
    }
}
