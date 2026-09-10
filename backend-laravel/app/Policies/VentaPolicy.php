<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Usuario;

class VentaPolicy
{
    public function viewAny(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::DESPACHO_VENTAS, RolUsuario::ADMINISTRACION);
    }

    public function create(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::DESPACHO_VENTAS);
    }
}
