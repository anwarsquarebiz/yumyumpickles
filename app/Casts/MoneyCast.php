<?php

namespace App\Casts;

use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Casts an integer minor-unit column to a {@see Money} value object.
 *
 * @implements CastsAttributes<Money|null, Money|int|string|null>
 */
class MoneyCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        return Money::fromMinor((int) $value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, int|null>
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null) {
            return [$key => null];
        }

        if ($value instanceof Money) {
            return [$key => $value->amount];
        }

        if (is_int($value)) {
            return [$key => $value];
        }

        if (is_numeric($value)) {
            return [$key => (int) $value];
        }

        throw new InvalidArgumentException(
            sprintf('Attribute [%s] must be a Money instance or an integer amount in minor units.', $key)
        );
    }
}
