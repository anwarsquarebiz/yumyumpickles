<?php

namespace Database\Factories;

use App\Enums\InventoryPolicy;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * An in-stock variant priced between $5 and $200.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'title' => 'Default',
            'sku' => strtoupper(fake()->bothify('SKU-####-???')).'-'.fake()->unique()->numberBetween(1, 999999),
            'barcode' => fake()->ean13(),
            'price_amount' => fake()->numberBetween(500, 20000),
            'compare_at_price_amount' => null,
            'cost_amount' => null,
            'option1' => null,
            'option2' => null,
            'option3' => null,
            'inventory_quantity' => fake()->numberBetween(10, 100),
            'track_inventory' => true,
            'inventory_policy' => InventoryPolicy::Deny,
            'weight' => fake()->randomFloat(3, 0.05, 2),
            'weight_unit' => 'kg',
            'position' => 1,
        ];
    }

    public function outOfStock(): static
    {
        return $this->state(fn (array $attributes): array => [
            'inventory_quantity' => 0,
            'track_inventory' => true,
            'inventory_policy' => InventoryPolicy::Deny,
        ]);
    }

    /**
     * Out of stock but still sellable, because backorders are allowed.
     */
    public function backorderable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'inventory_quantity' => 0,
            'track_inventory' => true,
            'inventory_policy' => InventoryPolicy::Continue,
        ]);
    }

    public function untracked(): static
    {
        return $this->state(fn (array $attributes): array => [
            'track_inventory' => false,
            'inventory_quantity' => 0,
        ]);
    }

    public function onSale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'price_amount' => 1000,
            'compare_at_price_amount' => 2000,
        ]);
    }

    public function priced(int $minorAmount): static
    {
        return $this->state(fn (array $attributes): array => [
            'price_amount' => $minorAmount,
        ]);
    }

    public function withStock(int $quantity): static
    {
        return $this->state(fn (array $attributes): array => [
            'inventory_quantity' => $quantity,
            'track_inventory' => true,
        ]);
    }
}
