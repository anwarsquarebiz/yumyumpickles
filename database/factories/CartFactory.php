<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * A guest cart, which is the common case.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'token' => (string) Str::uuid(),
            'user_id' => null,
            'coupon_id' => null,
            'currency' => config('shop.currency.code', 'USD'),
            'last_activity_at' => now(),
        ];
    }

    public function forUser(?User $user = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => $user?->getKey() ?? User::factory()->customer(),
        ]);
    }

    public function withCoupon(?Coupon $coupon = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'coupon_id' => $coupon?->getKey() ?? Coupon::factory(),
        ]);
    }

    public function abandoned(): static
    {
        return $this->state(fn (array $attributes): array => [
            'last_activity_at' => now()->subDays(
                (int) config('shop.cart.lifetime_days', 30) + 1
            ),
        ]);
    }
}
