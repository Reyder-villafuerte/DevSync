<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\SolicitudCambioZona;
use App\Models\Usuario;

class SolicitudCambioZonaPolicy
{
    public function viewAny(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::ADMINISTRACION);
    }

    public function create(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::PRODUCTOR);
    }

    public function resolver(Usuario $u, ?SolicitudCambioZona $s = null): bool
    {
        return $u->esRol(RolUsuario::ADMINISTRACION);
    }
}
