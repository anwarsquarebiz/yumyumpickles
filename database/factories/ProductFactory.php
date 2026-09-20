<?php

namespace Database\Factories;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * A product that is live on the storefront by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(12),
            'body_html' => '<p>'.fake()->paragraph().'</p>',
            'status' => ProductStatus::Active,
            'vendor' => fake()->company(),
            'product_type' => fake()->randomElement(['Tablets', 'Capsules', 'Syrup', 'Topical', 'Device']),
            'seo_title' => null,
            'seo_description' => null,
            'published_at' => now()->subDay(),
            'position' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Archived,
        ]);
    }

    /**
     * Active but scheduled to go live later, so it must stay hidden.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ProductStatus::Active,
            'published_at' => now()->addWeek(),
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes): array => [
            'published_at' => null,
        ]);
    }
}
