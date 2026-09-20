<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsDisplayMoney;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A product as shown in a grid. Expects the `variants` and `images` relations to
 * be loaded; `min_price_amount` is provided by the repository's withMin().
 *
 * @mixin Product
 */
class ProductSummaryResource extends JsonResource
{
    use PresentsDisplayMoney;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $firstImage = $this->images->first();
        $metadata = $this->metadata ?? [];
        $default = $this->variants->first(fn ($variant): bool => $variant->option1 === '400g')
            ?? $this->variants->sortBy('position')->first();
        $price = $this->displayMoney($default?->price() ?? $this->priceFrom());
        $compare = $this->displayMoney($default?->compare_at_price_amount);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'name' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'story' => $metadata['story'] ?? $this->description,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'category' => $this->product_type,
            'spice' => $metadata['spice'] ?? 'Medium',
            'badge' => $metadata['badge'] ?? null,
            'is_new' => (bool) ($metadata['is_new'] ?? false),
            'ingredients' => $metadata['ingredients'] ?? [],
            'nutrition' => $metadata['nutrition'] ?? [],
            'pairs_with' => $metadata['pairs_with'] ?? [],
            'url' => route('product.show', $this->slug),
            'price_from' => $this->displayMoney($this->priceFrom()),
            'compare_at_price' => $this->displayMoney($this->highestCompareAtPrice()),
            'price' => $this->major($price),
            'originalPrice' => $this->major($compare ?? $price),
            'weight' => $default?->option1 ?? $default?->title ?? '400g',
            'weights' => $this->variants->sortBy('position')->values()->map(fn ($variant): array => [
                'label' => $variant->option1 ?: $variant->displayTitle(),
                'price' => $this->major($this->displayMoney($variant->price())),
                'originalPrice' => $this->major($this->displayMoney($variant->compare_at_price_amount) ?? $this->displayMoney($variant->price())),
                'variant_id' => $variant->id,
            ])->all(),
            'default_variant_id' => $default?->id,
            'rating' => (float) ($metadata['rating'] ?? 4.8),
            'reviews' => (int) ($this->approved_reviews_count ?? $metadata['reviews'] ?? 120),
            'on_sale' => $this->variants->contains(fn ($variant): bool => $variant->isOnSale()),
            'in_stock' => $this->isInStock(),
            'inStock' => $this->isInStock(),
            'variant_count' => $this->variants->count(),
            'image_position' => 'center',
            'imagePosition' => 'center',
            'image_url' => $firstImage?->url(),
            'image' => $firstImage === null ? null : [
                'url' => $firstImage->url(),
                'alt' => $firstImage->alt ?? $this->title,
            ],
        ];
    }

    private function major(?\App\Support\Money $money): float
    {
        return $money === null ? 0.0 : (float) $money->toDecimal();
    }

    /**
     * The lowest variant price, preferring the aggregate the repository selected
     * so a grid does not need every variant hydrated to show "from" pricing.
     */
    private function priceFrom(): Money
    {
        if ($this->min_price_amount !== null) {
            return Money::fromMinor((int) $this->min_price_amount);
        }

        $lowest = $this->variants->min(fn ($variant): int => $variant->price()->amount);

        return Money::fromMinor((int) ($lowest ?? 0));
    }

    private function highestCompareAtPrice(): ?Money
    {
        $highest = $this->variants
            ->filter(fn ($variant): bool => $variant->isOnSale())
            ->max(fn ($variant): int => $variant->compare_at_price_amount->amount);

        return $highest === null ? null : Money::fromMinor((int) $highest);
    }
}
