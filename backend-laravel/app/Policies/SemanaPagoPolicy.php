<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Usuario;

class SemanaPagoPolicy
{
    public function viewAny(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::ADMINISTRACION, RolUsuario::JEFE_PRODUCCION);
    }

    public function gestionar(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::ADMINISTRACION);
    }
}
