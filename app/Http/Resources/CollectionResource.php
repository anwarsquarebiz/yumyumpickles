<?php

namespace App\Http\Resources;

use App\Models\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Collection
 */
class CollectionResource extends JsonResource
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
            'description' => $this->description,
            'status' => $this->status->value,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'meta_title' => $this->metaTitle(),
            'meta_description' => $this->metaDescription(),
            'position' => $this->position,
            'published_at' => $this->published_at?->toDateTimeString(),
            'is_published' => $this->isPublished(),
            'image_url' => $this->imageUrl(),
            'url' => route('collections.show', $this->slug),
            'products_count' => $this->whenCounted('products'),
            'product_ids' => $this->whenLoaded('products', fn () => $this->products->pluck('id')),
        ];
    }
}
