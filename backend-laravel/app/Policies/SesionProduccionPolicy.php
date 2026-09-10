<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\SesionProduccion;
use App\Models\Usuario;

class SesionProduccionPolicy
{
    public function viewAny(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::JEFE_PRODUCCION, RolUsuario::ADMINISTRACION);
    }

    public function create(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::JEFE_PRODUCCION);
    }

    public function completar(Usuario $u, SesionProduccion $s): bool
    {
        return $u->esRol(RolUsuario::JEFE_PRODUCCION);
    }
}
