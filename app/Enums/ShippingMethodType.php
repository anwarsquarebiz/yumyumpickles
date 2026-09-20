<?php

namespace App\Enums;

enum ShippingMethodType: string
{
    /**
     * Always charge the configured rate.
     */
    case FlatRate = 'flat_rate';

    /**
     * Charge the configured rate until the cart subtotal reaches a threshold, then ship free.
     */
    case FreeOverThreshold = 'free_over_threshold';

    /**
     * Charge the configured rate for every started weight unit in the cart.
     */
    case WeightBased = 'weight_based';

    public function label(): string
    {
        return match ($this) {
            self::FlatRate => 'Flat rate',
            self::FreeOverThreshold => 'Free over threshold',
            self::WeightBased => 'Weight based',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $type): array => ['value' => $type->value, 'label' => $type->label()],
            self::cases(),
        );
    }
}
