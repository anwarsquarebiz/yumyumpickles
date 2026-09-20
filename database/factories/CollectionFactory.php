<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Collection>
 */
class CollectionFactory extends Factory
{
    /**
     * A published collection by default.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(2, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->sentence(10),
            'image_disk' => null,
            'image_path' => null,
            'status' => PublishStatus::Published,
            'seo_title' => null,
            'seo_description' => null,
            'position' => fake()->numberBetween(0, 20),
            'published_at' => now()->subDay(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublishStatus::Draft,
            'published_at' => null,
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PublishStatus::Published,
            'published_at' => now()->addWeek(),
        ]);
    }
}
