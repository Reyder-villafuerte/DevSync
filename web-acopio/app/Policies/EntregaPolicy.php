<?php

namespace App\Policies;

use App\Models\Entrega;
use App\Models\User;

class EntregaPolicy
{
    public function view(User $user, Entrega $entrega): bool
    {
        return $user->status === 'ACTIVO' && ($user->hasRole('admin') || $user->hasRole('supervisor') || ($user->hasRole('acopiador') && $entrega->acopiador_id === $user->acopiador?->id) || ($user->hasRole('productor') && $entrega->productor_id === $user->productor?->id));
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('entregas.create');
    }
}
