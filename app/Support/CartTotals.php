<?php

namespace App\Support;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/**
 * The money side of a cart, computed rather than stored so a price or coupon
 * change is always reflected the next time the cart is read.
 *
 * Shipping and tax deliberately live outside the cart: they depend on a
 * destination address, which only exists at checkout.
 *
 * @implements Arrayable<string, mixed>
 */
final class CartTotals implements Arrayable, JsonSerializable
{
    public function __construct(
        public readonly Money $subtotal,
        public readonly Money $discount,
        public readonly int $itemCount,
        public readonly ?string $couponCode = null,
        public readonly ?Money $quantityDiscount = null,
        public readonly ?string $quantityLabel = null,
        public readonly ?Money $bogoDiscount = null,
        public readonly ?Money $couponDiscount = null,
        public readonly ?string $couponLabel = null,
        public readonly bool $freeShipping = false,
        public readonly int $pointsEarned = 0,
    ) {}

    public static function empty(): self
    {
        return new self(Money::zero(), Money::zero(), 0);
    }

    /**
     * Subtotal less discount, floored at zero.
     */
    public function total(): Money
    {
        return $this->subtotal->minus($this->discount)->atLeastZero();
    }

    public function hasDiscount(): bool
    {
        return $this->discount->isPositive() || $this->freeShipping;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal->toArray(),
            'discount' => $this->discount->toArray(),
            'total' => $this->total()->toArray(),
            'item_count' => $this->itemCount,
            'coupon_code' => $this->couponCode,
            'quantity_discount' => ($this->quantityDiscount ?? Money::zero())->toArray(),
            'quantity_label' => $this->quantityLabel,
            'bogo_discount' => ($this->bogoDiscount ?? Money::zero())->toArray(),
            'coupon_discount' => ($this->couponDiscount ?? Money::zero())->toArray(),
            'coupon_label' => $this->couponLabel,
            'free_shipping' => $this->freeShipping,
            'points_earned' => $this->pointsEarned,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
