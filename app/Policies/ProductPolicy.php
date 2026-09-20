<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Catalog management is staff-only. The admin middleware already blocks
     * customers from the panel; these checks keep the rule enforced even if a
     * product action is ever reached from somewhere else.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function reorder(User $user): bool
    {
        return $user->isAdmin();
    }
}
