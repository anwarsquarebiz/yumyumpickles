<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Cart\CartResolver;
use Illuminate\Auth\Events\Login;

/**
 * Keeps a guest's cart when they sign in, so items chosen before logging in are
 * not silently lost.
 */
class MergeGuestCartOnLogin
{
    public function __construct(private readonly CartResolver $resolver) {}

    public function handle(Login $event): void
    {
        if (! $event->user instanceof User) {
            return;
        }

        $this->resolver->claimFor($event->user);
    }
}
