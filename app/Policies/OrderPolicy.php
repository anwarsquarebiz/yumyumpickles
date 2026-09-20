<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->isAdmin() || $order->user_id === $user->getKey();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }

    public function refund(User $user, Order $order): bool
    {
        return $user->isAdmin();
    }
}
