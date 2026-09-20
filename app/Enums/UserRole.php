<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::Customer => 'Customer',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $role): array => ['value' => $role->value, 'label' => $role->label()],
            self::cases(),
        );
    }
}
