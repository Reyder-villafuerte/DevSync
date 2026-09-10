<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\Liquidacion;
use App\Models\Usuario;

class LiquidacionPolicy
{
    public function viewAny(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::ADMINISTRACION, RolUsuario::JEFE_PRODUCCION);
    }

    public function view(Usuario $u, Liquidacion $liq): bool
    {
        // El productor puede ver la suya desde la app.
        return $this->viewAny($u)
            || ($u->esRol(RolUsuario::PRODUCTOR) && $u->productor?->id === $liq->productor_id);
    }

    public function generar(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::ADMINISTRACION);
    }

    public function marcarEntregada(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::ADMINISTRACION);
    }
}
