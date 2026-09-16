<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function review(User $actor, User $worker): bool
    {
        return $actor->hasPermission('users.manage') && $actor->id !== $worker->id;
    }
}
