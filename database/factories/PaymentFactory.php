<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'gateway' => 'fake',
            'gateway_reference' => (string) Str::uuid(),
            'status' => PaymentStatus::Pending,
            'amount' => 3099,
            'currency' => 'USD',
            'redirect_url' => null,
            'request_payload' => null,
            'response_payload' => null,
            'failure_reason' => null,
            'paid_at' => null,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::Failed,
            'failure_reason' => 'Declined',
        ]);
    }
}
