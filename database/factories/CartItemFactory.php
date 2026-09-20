<?php

namespace Database\Factories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CartItem>
 */
class CartItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cart_id' => Cart::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'quantity' => fake()->numberBetween(1, 3),
            'unit_price_amount' => fake()->numberBetween(500, 20000),
        ];
    }

    public function forVariant(ProductVariant $variant, int $quantity = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'product_variant_id' => $variant->getKey(),
            'quantity' => $quantity,
            'unit_price_amount' => $variant->price()->amount,
        ]);
    }
}
