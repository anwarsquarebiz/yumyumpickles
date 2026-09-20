<?php

namespace App\Enums;

enum AddressType: string
{
    case Shipping = 'shipping';
    case Billing = 'billing';

    public function label(): string
    {
        return match ($this) {
            self::Shipping => 'Shipping',
            self::Billing => 'Billing',
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
