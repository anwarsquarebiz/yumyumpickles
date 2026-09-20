<?php

namespace App\Http\Resources;

use App\Enums\CouponType;
use App\Models\Cart;
use App\Services\Currency\CurrencyConverter;
use App\Services\Currency\CurrencyService;
use App\Support\CartTotals;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full cart for the /cart page. Totals are passed in rather than recomputed
 * here so the page and the shared header badge always agree.
 *
 * @mixin Cart
 */
class CartResource extends JsonResource
{
    public function __construct(Cart $cart, private readonly CartTotals $totals)
    {
        parent::__construct($cart);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $converter = app(CurrencyConverter::class);

        return [
            'id' => $this->id,
            'currency' => app(CurrencyService::class)->current(),
            'items' => CartItemResource::collection(
                $this->items->sortBy('id')->values()
            ),
            'totals' => $converter->presentTotals($this->totals),
            'coupon' => $this->coupon === null ? null : [
                'code' => $this->coupon->code,
                'description' => $this->coupon->description,
                'value' => $this->couponDisplayValue($converter),
                'applied' => $this->totals->hasDiscount(),
            ],
        ];
    }

    private function couponDisplayValue(CurrencyConverter $converter): string
    {
        if ($this->coupon->type === CouponType::Percentage || $this->coupon->type === CouponType::FreeShipping) {
            return $this->coupon->displayValue();
        }

        return $converter->forDisplay(Money::fromMinor($this->coupon->value))->format();
    }
}
