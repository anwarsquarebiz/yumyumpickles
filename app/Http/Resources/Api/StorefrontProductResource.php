<?php

namespace App\Http\Resources\Api;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\StorefrontAsset;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 */
class StorefrontProductResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $metadata = $this->metadata ?? [];
        $default = $this->defaultWeightVariant();
        $images = $this->images;

        return [
            'id' => $this->slug,
            'numeric_id' => $this->id,
            'name' => $this->title,
            'category' => $this->collections->first()?->title ?? $this->product_type ?? '',
            'spice' => $metadata['spice'] ?? 'Medium',
            'price' => $this->major($default?->price()->amount ?? 0),
            'originalPrice' => $this->major($default?->compare_at_price_amount?->amount ?? $default?->price()->amount ?? 0),
            'rating' => $this->approvedRating(),
            'reviews' => $this->approvedReviewCount(),
            'weight' => $default?->option1 ?? $default?->title ?? '',
            'weights' => $this->variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'label' => $variant->option1 ?: $variant->displayTitle(),
                'price' => $this->major($variant->price()->amount),
                'originalPrice' => $this->major($variant->compare_at_price_amount?->amount ?? $variant->price()->amount),
            ])->values(),
            'description' => $this->description ?? '',
            'story' => $metadata['story'] ?? $this->description ?? '',
            'ingredients' => $metadata['ingredients'] ?? [],
            'nutrition' => $metadata['nutrition'] ?? [],
            'image' => $images->first()?->url() ?? StorefrontAsset::url('yumyum-four.png'),
            'gallery' => $images->map(fn ($image): array => [
                'src' => $image->url(),
                'position' => 'center',
                'alt' => $image->alt ?? $this->title,
            ])->values(),
            'imagePosition' => 'center',
            'inStock' => $this->isInStock(),
            'pairsWith' => $metadata['pairs_with'] ?? [],
            'isNew' => (bool) ($metadata['is_new'] ?? false),
            'badge' => $metadata['badge'] ?? null,
        ];
    }

    private function defaultWeightVariant(): ?ProductVariant
    {
        return $this->variants->first(fn (ProductVariant $variant): bool => $variant->option1 === '400g')
            ?? $this->variants->first();
    }

    private function major(?int $amount): int
    {
        return (int) round(($amount ?? 0) / 100);
    }

    private function approvedReviewCount(): int
    {
        if (isset($this->approved_reviews_count)) {
            return (int) $this->approved_reviews_count;
        }

        return $this->reviews->where('status', ReviewStatus::Approved)->count();
    }

    private function approvedRating(): float
    {
        $reviews = $this->reviews->where('status', ReviewStatus::Approved);

        if ($reviews->isEmpty()) {
            return 4.8;
        }

        return round($reviews->avg('rating'), 1);
    }
}
