<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = 2500;
        $shipping = 599;
        $discount = 0;
        $tax = 0;
        $grand = $subtotal - $discount + $shipping + $tax;

        return [
            'order_number' => '#'.fake()->unique()->numberBetween(1001, 999999),
            'user_id' => User::factory()->customer(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->numerify('##########'),
            'status' => OrderStatus::Pending,
            'currency' => 'USD',
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discount,
            'shipping_amount' => $shipping,
            'tax_amount' => $tax,
            'grand_total_amount' => $grand,
            'refunded_amount' => 0,
            'coupon_id' => null,
            'coupon_code' => null,
            'shipping_address' => [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'company' => null,
                'address_line1' => '1 Computing Lane',
                'address_line2' => null,
                'city' => 'London',
                'province' => 'Greater London',
                'postal_code' => 'SW1A 1AA',
                'country_code' => 'GB',
                'phone' => '07000000000',
            ],
            'billing_address' => [
                'first_name' => 'Ada',
                'last_name' => 'Lovelace',
                'company' => null,
                'address_line1' => '1 Computing Lane',
                'address_line2' => null,
                'city' => 'London',
                'province' => 'Greater London',
                'postal_code' => 'SW1A 1AA',
                'country_code' => 'GB',
                'phone' => '07000000000',
            ],
            'shipping_method_name' => 'Standard shipping',
            'customer_note' => null,
            'staff_note' => null,
            'placed_at' => now(),
            'cancelled_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Paid,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Processing,
        ]);
    }

    public function shipped(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Shipped,
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Delivered,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => OrderStatus::Refunded,
            'refunded_amount' => $attributes['grand_total_amount'] ?? 0,
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => null,
        ]);
    }
}
