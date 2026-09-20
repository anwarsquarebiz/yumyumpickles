<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsDisplayMoney;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A cart line. Expects `variant.product` and `variant.images` to be loaded.
 *
 * @mixin CartItem
 */
class CartItemResource extends JsonResource
{
    use PresentsDisplayMoney;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $variant = $this->variant;
        $product = $variant->product;
        $image = $variant->images->first() ?? $product->images->first();

        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'unit_price' => $this->displayMoney($this->unitPrice()),
            'line_total' => $this->displayMoney($this->lineTotal()),
            'max_quantity' => max($this->quantity, $variant->purchasableQuantity()),
            'in_stock' => $variant->canFulfill($this->quantity),
            'variant' => [
                'id' => $variant->id,
                'title' => $variant->displayTitle(),
                'sku' => $variant->sku,
                'options' => $variant->optionValues(),
            ],
            'product' => [
                'id' => $product->id,
                'title' => $product->title,
                'slug' => $product->slug,
                'url' => route('product.show', $product->slug),
            ],
            'image' => $image === null ? null : [
                'url' => $image->url(),
                'alt' => $image->alt ?? $product->title,
            ],
        ];
    }
}
