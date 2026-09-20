<?php

namespace App\Services\Currency;

use App\Support\CartTotals;
use App\Support\Money;

/**
 * Converts between the USD base used for storage and the visitor's display
 * currency. Arithmetic still happens on base amounts; only presentation and
 * catalog price filters go through this class.
 */
class CurrencyConverter
{
    public function __construct(
        private readonly CurrencyService $currencies,
        private readonly ExchangeRateService $rates,
    ) {}

    public function convert(Money $money, string $to): Money
    {
        $to = strtoupper($to);

        if ($money->currency === $to) {
            return $money;
        }

        $base = $this->toBase($money);

        if ($to === $this->currencies->base()) {
            return $base;
        }

        return Money::fromMinor(
            (int) round($base->amount * $this->rates->rate($to)),
            $to,
        );
    }

    /**
     * Express a display-currency amount back in the store's base currency.
     */
    public function toBase(Money $money): Money
    {
        $base = $this->currencies->base();

        if ($money->currency === $base) {
            return Money::fromMinor($money->amount, $base);
        }

        $rate = $this->rates->rate($money->currency);

        if ($rate <= 0) {
            return Money::fromMinor($money->amount, $base);
        }

        return Money::fromMinor((int) round($money->amount / $rate), $base);
    }

    /**
     * Convert a catalog price filter typed in the display currency into a
     * base-currency decimal the product repository can query with.
     */
    public function toBaseDecimal(?string $amount): ?string
    {
        if ($amount === null || $amount === '') {
            return null;
        }

        $display = Money::fromDecimal($amount, $this->currencies->current());

        return $this->toBase($display)->toDecimal();
    }

    public function forDisplay(Money $money): Money
    {
        return $this->convert($money, $this->currencies->current());
    }

    /**
     * @return array{amount: int, currency: string, formatted: string, decimal: string}
     */
    public function present(Money $money): array
    {
        return $this->forDisplay($money)->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function presentTotals(CartTotals $totals): array
    {
        return [
            'subtotal' => $this->present($totals->subtotal),
            'discount' => $this->present($totals->discount),
            'total' => $this->present($totals->total()),
            'item_count' => $totals->itemCount,
            'coupon_code' => $totals->couponCode,
        ];
    }
}
