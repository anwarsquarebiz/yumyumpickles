<?php

namespace Database\Factories;

use App\Enums\PublishStatus;
use App\Models\Blog;
use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<BlogPost>
 */
class BlogPostFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = Str::title(fake()->unique()->sentence(4));

        return [
            'blog_id' => Blog::factory(),
            'blog_category_id' => null,
            'user_id' => User::factory()->admin(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numberBetween(1, 999999),
            'excerpt' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraphs(4, true).'</p>',
            'featured_image_disk' => null,
            'featured_image_path' => null,
            'status' => PublishStatus::Published,
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
}
