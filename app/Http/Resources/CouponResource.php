<?php

namespace App\Http\Resources;

use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Coupon
 */
class CouponResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'description' => $this->description,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'value' => $this->value,
            'value_input' => $this->editableValue(),
            'display_value' => $this->displayValue(),
            'minimum_subtotal' => $this->minimum_subtotal_amount,
            'minimum_subtotal_input' => $this->minimum_subtotal_amount?->toDecimal(),
            'usage_limit' => $this->usage_limit,
            'usage_limit_per_customer' => $this->usage_limit_per_customer,
            'used_count' => $this->used_count,
            'starts_at' => $this->starts_at?->toDateTimeString(),
            'expires_at' => $this->expires_at?->toDateTimeString(),
            'is_active' => $this->is_active,
            'redeemable' => $this->isRedeemable(),
            'status_label' => $this->statusLabel(),
        ];
    }

    /**
     * Why a code is or is not usable right now, so the list does not require the
     * operator to compare dates and counters themselves.
     */
    private function statusLabel(): string
    {
        return match (true) {
            ! $this->is_active => 'Disabled',
            $this->hasExpired() => 'Expired',
            ! $this->hasStarted() => 'Scheduled',
            $this->hasReachedUsageLimit() => 'Limit reached',
            default => 'Active',
        };
    }
}
