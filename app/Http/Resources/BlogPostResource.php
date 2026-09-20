<?php

namespace App\Http\Resources;

use App\Models\BlogPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BlogPost
 */
class BlogPostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'blog_id' => $this->blog_id,
            'blog_category_id' => $this->blog_category_id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'status' => $this->status->value,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'meta_title' => $this->metaTitle(),
            'meta_description' => $this->metaDescription(),
            'published_at' => $this->published_at?->toDateTimeString(),
            'is_published' => $this->isPublished(),
            'featured_image_url' => $this->featuredImageUrl(),
            'url' => $this->relationLoaded('blog') && $this->blog
                ? route('blogs.posts.show', [$this->blog->slug, $this->slug])
                : null,
            'blog' => new BlogResource($this->whenLoaded('blog')),
            'category' => $this->whenLoaded('category', fn () => $this->category === null ? null : [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'author' => $this->whenLoaded('author', fn () => $this->author === null ? null : [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ]),
        ];
    }
}
