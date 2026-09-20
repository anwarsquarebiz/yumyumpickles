<?php

namespace App\Support;

final class CheckoutTotals
{
    public function __construct(
        public readonly Money $subtotal,
        public readonly Money $discount,
        public readonly Money $shipping,
        public readonly Money $tax,
        public readonly Money $grandTotal,
        public readonly ?string $couponCode = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal->toArray(),
            'discount' => $this->discount->toArray(),
            'shipping' => $this->shipping->toArray(),
            'tax' => $this->tax->toArray(),
            'grand_total' => $this->grandTotal->toArray(),
            'coupon_code' => $this->couponCode,
        ];
    }
}
