<?php

namespace Database\Factories;

use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Refund>
 */
class RefundFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'payment_id' => Payment::factory(),
            'user_id' => null,
            'amount' => 1000,
            'reason' => 'Customer request',
            'status' => RefundStatus::Succeeded,
            'gateway_reference' => null,
            'restock' => false,
            'processed_at' => now(),
        ];
    }
}
