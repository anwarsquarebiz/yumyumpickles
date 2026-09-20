<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOption>
 */
class ProductOptionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => fake()->unique()->randomElement(['Strength', 'Size', 'Pack', 'Flavour', 'Colour']),
            'position' => 1,
            'values' => ['Small', 'Large'],
        ];
    }

    /**
     * @param  array<int, string>  $values
     */
    public function named(string $name, int $position, array $values): static
    {
        return $this->state(fn (array $attributes): array => [
            'name' => $name,
            'position' => $position,
            'values' => $values,
        ]);
    }
}
