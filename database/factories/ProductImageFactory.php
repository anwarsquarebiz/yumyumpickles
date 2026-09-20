<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'product_variant_id' => null,
            'disk' => 'public',
            'path' => 'products/'.fake()->unique()->uuid().'.jpg',
            'alt' => fake()->sentence(3),
            'position' => 1,
        ];
    }
}
