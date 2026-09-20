<?php

namespace Database\Factories;

use App\Enums\ShippingMethodType;
use App\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShippingMethod>
 */
class ShippingMethodFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Standard shipping',
            'description' => '3-5 business days',
            'type' => ShippingMethodType::FlatRate,
            'rate_amount' => 599,
            'free_over_amount' => null,
            'is_active' => true,
            'position' => 1,
        ];
    }

    public function freeOver(int $thresholdMinor = 5000): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'Free over threshold',
            'type' => ShippingMethodType::FreeOverThreshold,
            'rate_amount' => 799,
            'free_over_amount' => $thresholdMinor,
        ]);
    }

    public function weightBased(int $rateMinor = 250): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => 'By weight',
            'type' => ShippingMethodType::WeightBased,
            'rate_amount' => $rateMinor,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
