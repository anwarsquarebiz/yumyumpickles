<?php

namespace App\Http\Resources;

use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Page
 */
class PageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'status' => $this->status->value,
            'template' => $this->template->value,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'meta_title' => $this->metaTitle(),
            'meta_description' => $this->metaDescription(),
            'published_at' => $this->published_at?->toDateTimeString(),
            'is_published' => $this->isPublished(),
            'url' => in_array($this->slug, ['our-story', 'faq', 'contact', 'shipping', 'refunds', 'privacy', 'terms'], true)
                ? url('/'.$this->slug)
                : route('pages.show', $this->slug),
        ];
    }
}
