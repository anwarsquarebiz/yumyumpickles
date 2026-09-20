<?php

namespace App\Policies;

use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $actor, User $customer): bool
    {
        return $actor->isAdmin();
    }
}
