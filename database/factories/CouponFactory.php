<?php

namespace Database\Factories;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * An active $10 off code with no limits.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => mb_strtoupper(fake()->unique()->bothify('SAVE####')),
            'description' => fake()->sentence(4),
            'type' => CouponType::FixedAmount,
            'value' => 1000,
            'minimum_subtotal_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_customer' => null,
            'used_count' => 0,
            'starts_at' => null,
            'expires_at' => null,
            'is_active' => true,
        ];
    }

    public function fixed(int $minorAmount): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CouponType::FixedAmount,
            'value' => $minorAmount,
        ]);
    }

    /**
     * @param  int  $percent  Whole percent, converted to the stored basis points.
     */
    public function percentage(int $percent): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => CouponType::Percentage,
            'value' => $percent * 100,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->subMonth(),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'starts_at' => now()->addWeek(),
            'expires_at' => now()->addMonth(),
        ]);
    }

    public function exhausted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_limit' => 5,
            'used_count' => 5,
        ]);
    }

    public function minimumSubtotal(int $minorAmount): static
    {
        return $this->state(fn (array $attributes): array => [
            'minimum_subtotal_amount' => $minorAmount,
        ]);
    }

    public function limitedPerCustomer(int $limit = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'usage_limit_per_customer' => $limit,
        ]);
    }
}
