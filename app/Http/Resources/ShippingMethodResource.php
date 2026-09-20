<?php

namespace App\Http\Resources;

use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShippingMethod
 */
class ShippingMethodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'rate' => $this->rate_amount,
            'free_over' => $this->free_over_amount,
            'is_active' => $this->is_active,
            'position' => $this->position,
        ];
    }
}
