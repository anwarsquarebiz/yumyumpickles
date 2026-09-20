<?php

namespace App\Policies;

use App\Models\Blog;
use App\Models\User;

class BlogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Blog $blog): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Blog $blog): bool
    {
        return $user->isAdmin();
    }
}
