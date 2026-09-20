<?php

namespace App\Support;

use InvalidArgumentException;
use JsonSerializable;
use Stringable;

/**
 * An immutable monetary amount held in minor currency units (for example cents).
 *
 * Storing integers rather than floats keeps every subtotal, discount, tax, shipping
 * and refund calculation exact, which matters because these values are reconciled
 * against a payment gateway.
 */
final class Money implements JsonSerializable, Stringable
{
    /**
     * Defaults are held statically rather than read from config on every call so
     * this stays a plain value object that unit tests can use without booting
     * the framework. AppServiceProvider applies the configured values at boot.
     */
    private static string $defaultCurrency = 'USD';

    private static int $defaultDecimals = 2;

    /** @var array<string, string> */
    private static array $currencySymbols = [
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',
        'AUD' => 'A$',
        'CAD' => 'C$',
    ];

    public function __construct(
        public readonly int $amount,
        public readonly string $currency = 'USD',
    ) {}

    /**
     * Apply the store's configured currency. Called once from AppServiceProvider.
     *
     * @param  array<string, string>  $symbols
     */
    public static function configure(string $currency, int $decimals, array $symbols = []): void
    {
        self::$defaultCurrency = $currency;
        self::$defaultDecimals = $decimals;

        if ($symbols !== []) {
            self::$currencySymbols = $symbols;
        }
    }

    public static function zero(?string $currency = null): self
    {
        return new self(0, $currency ?? self::defaultCurrency());
    }

    /**
     * Build from a raw integer in minor units, which is how amounts are persisted.
     */
    public static function fromMinor(int $amount, ?string $currency = null): self
    {
        return new self($amount, $currency ?? self::defaultCurrency());
    }

    /**
     * Build from a human-entered major-unit value such as "19.99" from an admin form.
     */
    public static function fromDecimal(int|float|string $value, ?string $currency = null): self
    {
        if (! is_numeric($value)) {
            throw new InvalidArgumentException("Value [{$value}] is not a valid monetary amount.");
        }

        return new self(
            (int) round(((float) $value) * (10 ** self::decimals())),
            $currency ?? self::defaultCurrency(),
        );
    }

    public function plus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function minus(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    /**
     * Multiply by a whole number, used for line totals (unit price times quantity).
     */
    public function multipliedBy(int $multiplier): self
    {
        return new self($this->amount * $multiplier, $this->currency);
    }

    /**
     * Take a percentage expressed in basis points, where 10000 equals 100%.
     */
    public function percentage(int $basisPoints): self
    {
        return new self(
            (int) round($this->amount * $basisPoints / 10000),
            $this->currency,
        );
    }

    /**
     * Clamp to zero so a discount can never push a total negative.
     */
    public function atLeastZero(): self
    {
        return $this->isNegative() ? self::zero($this->currency) : $this;
    }

    /**
     * Never exceed the given ceiling, used to cap a discount at the cart subtotal.
     */
    public function cappedAt(self $ceiling): self
    {
        $this->assertSameCurrency($ceiling);

        return $this->amount > $ceiling->amount ? $ceiling : $this;
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function greaterThanOrEqualTo(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount >= $other->amount;
    }

    public function lessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount < $other->amount;
    }

    /**
     * Sum any number of amounts, returning zero when the list is empty.
     */
    public static function sum(self ...$amounts): self
    {
        if ($amounts === []) {
            return self::zero();
        }

        return array_reduce(
            array_slice($amounts, 1),
            fn (self $carry, self $item): self => $carry->plus($item),
            $amounts[0],
        );
    }

    /**
     * The major-unit representation, suitable for populating an admin form field.
     */
    public function toDecimal(): string
    {
        return number_format($this->amount / (10 ** self::decimals()), self::decimals(), '.', '');
    }

    public function format(): string
    {
        return self::symbol($this->currency).number_format(
            $this->amount / (10 ** self::decimals()),
            self::decimals(),
            '.',
            ',',
        );
    }

    /**
     * @return array{amount: int, currency: string, formatted: string, decimal: string}
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'currency' => $this->currency,
            'formatted' => $this->format(),
            'decimal' => $this->toDecimal(),
        ];
    }

    /**
     * @return array{amount: int, currency: string, formatted: string, decimal: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __toString(): string
    {
        return $this->format();
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                "Cannot combine [{$this->currency}] with [{$other->currency}]."
            );
        }
    }

    private static function defaultCurrency(): string
    {
        return self::$defaultCurrency;
    }

    private static function decimals(): int
    {
        return self::$defaultDecimals;
    }

    private static function symbol(string $currency): string
    {
        return self::$currencySymbols[$currency] ?? ($currency.' ');
    }
}
