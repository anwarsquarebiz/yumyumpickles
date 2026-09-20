<?php

namespace App\Enums;

enum PageTemplate: string
{
    case Default = 'default';
    case Contact = 'contact';

    public function label(): string
    {
        return match ($this) {
            self::Default => 'Default',
            self::Contact => 'Contact',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $template): array => ['value' => $template->value, 'label' => $template->label()],
            self::cases(),
        );
    }
}
