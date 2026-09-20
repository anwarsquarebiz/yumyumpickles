<?php

namespace App\Http\Resources;

use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full product, as rendered on /products/{slug} and in the admin editor.
 *
 * @mixin Product
 */
class ProductDetailResource extends JsonResource
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
            'body_html' => $this->body_html,
            'status' => $this->status->value,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'metadata' => $this->metadata ?? [],
            'meta_title' => $this->metaTitle(),
            'meta_description' => $this->metaDescription(),
            'published_at' => $this->published_at?->toDateTimeString(),
            'is_published' => $this->isPublished(),
            'in_stock' => $this->isInStock(),
            'url' => route('product.show', $this->slug),
            'name' => $this->title,
            'category' => $this->product_type,
            'spice' => ($this->metadata ?? [])['spice'] ?? 'Medium',
            'story' => ($this->metadata ?? [])['story'] ?? $this->description,
            'ingredients' => ($this->metadata ?? [])['ingredients'] ?? [],
            'nutrition' => ($this->metadata ?? [])['nutrition'] ?? [],
            'pairs_with' => ($this->metadata ?? [])['pairs_with'] ?? [],
            'badge' => ($this->metadata ?? [])['badge'] ?? null,
            'is_new' => (bool) (($this->metadata ?? [])['is_new'] ?? false),
            'rating' => round((float) ($this->relationLoaded('reviews') ? ($this->reviews->avg('rating') ?: 4.8) : 4.8), 1),
            'review_count' => $this->relationLoaded('reviews') ? $this->reviews->count() : 0,
            'customer_reviews' => $this->whenLoaded('reviews', fn () => $this->reviews->map(fn ($review): array => [
                'name' => $review->author_name,
                'city' => $review->author_city,
                'rating' => $review->rating,
                'date' => $review->reviewed_at?->format('j M Y'),
                'text' => $review->body,
            ])->values()),
            'gallery' => $this->images->map(fn ($image): array => [
                'src' => $image->url(),
                'alt' => $image->alt ?? $this->title,
                'position' => 'center',
            ])->values(),
            'default_variant_id' => optional(
                $this->variants->first(fn ($variant): bool => $variant->option1 === '400g')
                    ?? $this->variants->first()
            )->id,
            'variants' => ProductVariantResource::collection($this->variants),
            'images' => ProductImageResource::collection($this->images),
            'options' => $this->options->map(fn (ProductOption $option): array => [
                'id' => $option->id,
                'name' => $option->name,
                'position' => $option->position,
                'values' => $option->values ?? [],
            ])->values(),
            'collections' => $this->whenLoaded(
                'collections',
                fn () => CollectionSummaryResource::collection($this->collections),
            ),
        ];
    }
}
