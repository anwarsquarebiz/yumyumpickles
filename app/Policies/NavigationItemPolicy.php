<?php

namespace App\Policies;

use App\Models\NavigationItem;
use App\Models\User;

class NavigationItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, NavigationItem $navigationItem): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, NavigationItem $navigationItem): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin();
    }
}
