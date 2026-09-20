<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $unit = 1500;
        $quantity = 1;

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'product_title' => fake()->words(3, true),
            'variant_title' => 'Default',
            'sku' => strtoupper(fake()->bothify('SKU-####')),
            'unit_price_amount' => $unit,
            'quantity' => $quantity,
            'subtotal_amount' => $unit * $quantity,
            'discount_amount' => 0,
            'total_amount' => $unit * $quantity,
        ];
    }
}
