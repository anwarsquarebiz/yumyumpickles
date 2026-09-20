<?php

namespace App\Services\Content;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BlogService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createBlog(array $data): Blog
    {
        return Blog::query()->create($this->blogAttributes($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateBlog(Blog $blog, array $data): Blog
    {
        $blog->update($this->blogAttributes($data, $blog));

        return $blog->refresh();
    }

    public function deleteBlog(Blog $blog): void
    {
        $blog->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): BlogCategory
    {
        $slug = $this->uniqueSlug(BlogCategory::class, $data['slug'] ?? $data['name']);

        return BlogCategory::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPost(array $data, ?int $authorId = null): BlogPost
    {
        $post = BlogPost::query()->create($this->postAttributes($data, authorId: $authorId));
        $this->storeImage($post, $data['featured_image'] ?? null);

        return $post->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePost(BlogPost $post, array $data): BlogPost
    {
        $post->update($this->postAttributes($data, $post));

        if (($data['remove_featured_image'] ?? false) === true) {
            $this->deleteImage($post);
        }

        $this->storeImage($post, $data['featured_image'] ?? null);

        return $post->refresh();
    }

    public function deletePost(BlogPost $post): void
    {
        $this->deleteImage($post);
        $post->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function blogAttributes(array $data, ?Blog $existing = null): array
    {
        $attributes = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
        ];

        $requested = $data['slug'] ?? null;

        if ($requested !== null && $requested !== '') {
            $attributes['slug'] = $this->uniqueSlug(Blog::class, $requested, $existing?->getKey());
        } elseif ($existing === null) {
            $attributes['slug'] = $this->uniqueSlug(Blog::class, $data['title']);
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function postAttributes(array $data, ?BlogPost $existing = null, ?int $authorId = null): array
    {
        $blogId = (int) ($data['blog_id'] ?? $existing?->blog_id);

        $attributes = [
            'blog_id' => $blogId,
            'blog_category_id' => $data['blog_category_id'] ?? null,
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? null,
            'content' => $data['content'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ];

        if (array_key_exists('status', $data)) {
            $attributes['status'] = $data['status'];
        }

        if ($authorId !== null) {
            $attributes['user_id'] = $authorId;
        }

        $requested = $data['slug'] ?? null;

        if ($requested !== null && $requested !== '') {
            $attributes['slug'] = $this->uniquePostSlug($blogId, $requested, $existing?->getKey());
        } elseif ($existing === null) {
            $attributes['slug'] = $this->uniquePostSlug($blogId, $data['title']);
        }

        return $attributes;
    }

    private function uniqueSlug(string $model, string $source, ?int $ignore = null): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($model::query()
            ->where('slug', $slug)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function uniquePostSlug(int $blogId, string $source, ?int $ignore = null): string
    {
        $base = Str::slug($source) ?: 'post';
        $slug = $base;
        $suffix = 2;

        while (BlogPost::query()
            ->where('blog_id', $blogId)
            ->where('slug', $slug)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore))
            ->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function storeImage(BlogPost $post, mixed $image): void
    {
        if (! $image instanceof UploadedFile) {
            return;
        }

        $this->deleteImage($post);

        $disk = (string) config('shop.catalog.image_disk', 'public');

        $post->forceFill([
            'featured_image_disk' => $disk,
            'featured_image_path' => $image->store('blog', $disk),
        ])->save();
    }

    private function deleteImage(BlogPost $post): void
    {
        if ($post->featured_image_path === null || $post->featured_image_disk === null) {
            return;
        }

        Storage::disk($post->featured_image_disk)->delete($post->featured_image_path);

        $post->forceFill(['featured_image_disk' => null, 'featured_image_path' => null])->save();
    }
}
