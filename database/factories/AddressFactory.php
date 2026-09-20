<?php

namespace Database\Factories;

use App\Enums\AddressType;
use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'type' => AddressType::Shipping,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'company' => null,
            'address_line1' => fake()->streetAddress(),
            'address_line2' => null,
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'US',
            'phone' => fake()->numerify('##########'),
            'is_default' => false,
        ];
    }

    public function billing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => AddressType::Billing,
        ]);
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
        ]);
    }
}
