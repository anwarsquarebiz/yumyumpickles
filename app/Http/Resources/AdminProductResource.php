<?php

namespace App\Http\Resources;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Admin catalog listing row. Expects variants/images (and listing aggregates)
 * to be loaded by ProductRepository::paginateForAdmin().
 *
 * @mixin Product
 */
class AdminProductResource extends JsonResource
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
            'status' => $this->status->value,
            'vendor' => $this->vendor,
            'product_type' => $this->product_type,
            'published_at' => $this->published_at?->toIso8601String(),
            'variants_count' => (int) ($this->variants_count ?? $this->variants->count()),
            'min_price_amount' => $this->min_price_amount !== null ? (int) $this->min_price_amount : null,
            'total_inventory' => $this->total_inventory !== null ? (int) $this->total_inventory : null,
            'images' => ProductImageResource::collection($this->whenLoaded('images')),
        ];
    }
}
