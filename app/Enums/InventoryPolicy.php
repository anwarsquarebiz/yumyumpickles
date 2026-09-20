<?php

namespace App\Enums;

enum InventoryPolicy: string
{
    /**
     * Stop selling the variant once inventory reaches zero.
     */
    case Deny = 'deny';

    /**
     * Keep selling the variant after inventory reaches zero (backorder).
     */
    case Continue = 'continue';

    public function label(): string
    {
        return match ($this) {
            self::Deny => 'Stop selling when out of stock',
            self::Continue => 'Continue selling when out of stock',
        };
    }

    public function allowsOversell(): bool
    {
        return $this === self::Continue;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $policy): array => ['value' => $policy->value, 'label' => $policy->label()],
            self::cases(),
        );
    }
}
