<?php

namespace App\Http\Resources;

use App\Http\Resources\Concerns\PresentsDisplayMoney;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ProductVariant
 */
class ProductVariantResource extends JsonResource
{
    use PresentsDisplayMoney;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'display_title' => $this->displayTitle(),
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->displayMoney($this->price()),
            'compare_at_price' => $this->displayMoney($this->compare_at_price_amount),
            'option1' => $this->option1,
            'option2' => $this->option2,
            'option3' => $this->option3,
            'option_values' => $this->optionValues(),
            'inventory_quantity' => $this->inventory_quantity,
            'track_inventory' => $this->track_inventory,
            'inventory_policy' => $this->inventory_policy->value,
            'weight' => $this->weight,
            'weight_unit' => $this->weight_unit,
            'position' => $this->position,
            'in_stock' => $this->isInStock(),
            'low_stock' => $this->isLowStock(),
            'on_sale' => $this->isOnSale(),
            'max_quantity' => $this->purchasableQuantity(),
        ];
    }
}
