<?php

namespace App\Enums;

// Roles del ecosistema. El valor persiste en BD; la etiqueta es para UI.
enum RolUsuario: string
{
    case ACOPIADOR = 'acopiador';
    case SUPERVISOR_CALIDAD = 'supervisor_calidad';
    case PRODUCTOR = 'productor';
    case JEFE_PRODUCCION = 'jefe_produccion';
    case DESPACHO_VENTAS = 'despacho_ventas';
    case ADMINISTRACION = 'administracion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::ACOPIADOR => 'Acopiador',
            self::SUPERVISOR_CALIDAD => 'Supervisor de Calidad',
            self::PRODUCTOR => 'Productor',
            self::JEFE_PRODUCCION => 'Jefe de Producción',
            self::DESPACHO_VENTAS => 'Despacho y Ventas',
            self::ADMINISTRACION => 'Administración',
        };
    }
}
