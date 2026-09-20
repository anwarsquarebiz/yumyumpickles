<?php

namespace App\Enums;

enum CouponType: string
{
    /**
     * A flat discount stored in minor currency units.
     */
    case FixedAmount = 'fixed_amount';

    /**
     * A percentage discount stored as basis points (10000 = 100%).
     */
    case Percentage = 'percentage';

    /**
     * Waives shipping. The stored value is unused and kept at 0.
     */
    case FreeShipping = 'free_shipping';

    public function label(): string
    {
        return match ($this) {
            self::FixedAmount => 'Fixed amount',
            self::Percentage => 'Percentage',
            self::FreeShipping => 'Free shipping',
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
