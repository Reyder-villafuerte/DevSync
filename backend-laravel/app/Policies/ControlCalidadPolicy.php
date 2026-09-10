<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\ControlCalidad;
use App\Models\Usuario;

class ControlCalidadPolicy
{
    public function viewAny(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::SUPERVISOR_CALIDAD, RolUsuario::JEFE_PRODUCCION);
    }

    public function view(Usuario $u, ControlCalidad $c): bool
    {
        return $this->viewAny($u)
            || ($u->esRol(RolUsuario::PRODUCTOR) && $u->productor?->id === $c->productor_id);
    }

    /** Registrar una inspección: rol Supervisor de Calidad. */
    public function create(Usuario $u): bool
    {
        return $u->esRol(RolUsuario::SUPERVISOR_CALIDAD);
    }
}
