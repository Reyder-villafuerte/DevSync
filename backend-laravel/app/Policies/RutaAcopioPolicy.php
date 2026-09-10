<?php

namespace App\Policies;

use App\Enums\RolUsuario;
use App\Models\RutaAcopio;
use App\Models\Usuario;

class RutaAcopioPolicy
{
    public function view(Usuario $u, RutaAcopio $jornada): bool
    {
        return $u->esRol(RolUsuario::JEFE_PRODUCCION)
            || $this->esDueño($u, $jornada);
    }

    /** Cerrar la jornada: solo el acopiador dueño (admin pasa por Gate::before). */
    public function cerrar(Usuario $u, RutaAcopio $jornada): bool
    {
        return $this->esDueño($u, $jornada);
    }

    private function esDueño(Usuario $u, RutaAcopio $jornada): bool
    {
        return $u->esRol(RolUsuario::ACOPIADOR)
            && $jornada->acopiador?->usuario_id === $u->id;
    }
}
