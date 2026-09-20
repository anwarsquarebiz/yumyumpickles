<?php

namespace App\Enums;

enum NavigationLinkType: string
{
    case Home = 'home';
    case Catalog = 'catalog';
    case Collection = 'collection';
    case Page = 'page';
    case Blog = 'blog';
    case Url = 'url';

    public function label(): string
    {
        return match ($this) {
            self::Home => 'Home',
            self::Catalog => 'All products',
            self::Collection => 'Collection',
            self::Page => 'Page',
            self::Blog => 'Blog',
            self::Url => 'Custom URL',
        };
    }

    public function requiresResource(): bool
    {
        return match ($this) {
            self::Collection, self::Page, self::Blog => true,
            self::Home, self::Catalog, self::Url => false,
        };
    }

    public function requiresUrl(): bool
    {
        return $this === self::Url;
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
