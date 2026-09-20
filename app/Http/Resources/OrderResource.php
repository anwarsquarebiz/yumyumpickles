<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer_name' => $this->customerDisplayName(),
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_tone' => $this->status->tone(),
            'currency' => $this->currency,
            'subtotal' => $this->subtotal_amount,
            'discount' => $this->discount_amount,
            'shipping' => $this->shipping_amount,
            'tax' => $this->tax_amount,
            'grand_total' => $this->grand_total_amount,
            'refunded' => $this->refunded_amount,
            'refundable' => $this->refundableAmount(),
            'coupon_code' => $this->coupon_code,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'shipping_address_lines' => $this->addressLines($this->shipping_address),
            'billing_address_lines' => $this->addressLines($this->billing_address),
            'shipping_method_name' => $this->shipping_method_name,
            'customer_note' => $this->customer_note,
            'staff_note' => $this->staff_note,
            'placed_at' => $this->placed_at?->toDateTimeString(),
            'created_at' => $this->created_at?->toDateTimeString(),
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'timeline' => OrderStatusEventResource::collection($this->whenLoaded('statusEvents')),
            'payments' => PaymentResource::collection($this->whenLoaded('payments')),
            'refunds' => RefundResource::collection($this->whenLoaded('refunds')),
            'customer' => $this->whenLoaded('user', fn () => $this->user === null ? null : [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]),
            'items_count' => $this->whenCounted('items'),
            'allowed_transitions' => array_map(
                fn ($status): array => ['value' => $status->value, 'label' => $status->label()],
                $this->status->allowedTransitions(),
            ),
            'is_refundable' => $this->status->isRefundable() && $this->refundableAmount()->isPositive(),
            'is_paid' => $this->status->isPaid(),
            'meta_event_id' => is_array($this->ads_attribution) ? ($this->ads_attribution['event_id'] ?? null) : null,
        ];
    }
}
