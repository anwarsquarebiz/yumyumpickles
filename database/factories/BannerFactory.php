<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'subtitle' => fake()->sentence(8),
            'button_label' => 'Shop now',
            'button_url' => '/products',
            'image_disk' => 'public',
            'image_path' => 'banners/demo.jpg',
            'alt' => fake()->words(3, true),
            'position' => 1,
            'status' => PublishStatus::Published,
            'published_at' => now()->subDay(),
            'starts_at' => null,
            'ends_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublishStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function withoutImage(): static
    {
        return $this->state(fn (array $attributes): array => [
            'image_disk' => null,
            'image_path' => null,
        ]);
    }
}
