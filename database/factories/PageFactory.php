<?php

namespace Database\Factories;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Models\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->words(3, true));

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraphs(3, true).'</p>',
            'status' => PublishStatus::Published,
            'template' => PageTemplate::Default,
            'seo_title' => null,
            'seo_description' => null,
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

    public function contact(): static
    {
        return $this->state(fn (array $attributes): array => [
            'template' => PageTemplate::Contact,
        ]);
    }
}
