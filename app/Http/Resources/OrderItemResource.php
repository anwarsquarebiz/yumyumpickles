<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OrderItem
 */
class OrderItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,
            'product_title' => $this->product_title,
            'variant_title' => $this->variant_title,
            'sku' => $this->sku,
            'quantity' => $this->quantity,
            'unit_price' => $this->unit_price_amount,
            'subtotal' => $this->subtotal_amount,
            'discount' => $this->discount_amount,
            'total' => $this->total_amount,
        ];
    }
}
