<?php

namespace App\Services\Checkout;

use App\Enums\ShippingMethodType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ShippingMethod;
use App\Support\Money;
use Illuminate\Support\Collection;

class ShippingCalculator
{
    /**
     * @return array<int, array{method: ShippingMethod, amount: Money}>
     */
    public function quotes(Cart $cart): array
    {
        $merchandise = $cart->items->reduce(
            fn (Money $carry, CartItem $item): Money => $carry->plus($item->lineTotal()),
            Money::zero($cart->currency),
        );

        $weight = $this->weightKg($cart);

        return $this->activeMethods()
            ->map(fn (ShippingMethod $method): array => [
                'method' => $method,
                'amount' => $this->quote($method, $merchandise, $weight),
            ])
            ->values()
            ->all();
    }

    public function quote(ShippingMethod $method, Money $subtotal, float $weightKg = 0): Money
    {
        $rate = $method->rate_amount ?? Money::zero($subtotal->currency);

        return match ($method->type) {
            ShippingMethodType::FlatRate => $rate,
            ShippingMethodType::FreeOverThreshold => $this->freeOver($method, $subtotal, $rate),
            ShippingMethodType::WeightBased => $rate->multipliedBy(max(1, (int) ceil(max($weightKg, 0.001)))),
        };
    }

    public function findActive(int $id): ?ShippingMethod
    {
        return ShippingMethod::query()->active()->whereKey($id)->first();
    }

    /**
     * @return Collection<int, ShippingMethod>
     */
    public function activeMethods(): Collection
    {
        return ShippingMethod::query()->active()->get();
    }

    public function weightKg(Cart $cart): float
    {
        $cart->loadMissing('items.variant');

        return (float) $cart->items->sum(function (CartItem $item): float {
            return ((float) ($item->variant?->weight ?? 0)) * $item->quantity;
        });
    }

    private function freeOver(ShippingMethod $method, Money $subtotal, Money $rate): Money
    {
        $threshold = $method->free_over_amount;

        if ($threshold !== null && $subtotal->greaterThanOrEqualTo($threshold)) {
            return Money::zero($subtotal->currency);
        }

        return $rate;
    }
}
