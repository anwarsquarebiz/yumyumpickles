<?php

namespace Database\Factories;

use App\Enums\NavigationLinkType;
use App\Models\NavigationItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NavigationItem>
 */
class NavigationItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu' => NavigationItem::MenuHeader,
            'title' => 'All products',
            'type' => NavigationLinkType::Catalog,
            'resource_id' => null,
            'url' => null,
            'position' => 1,
        ];
    }

    public function home(): static
    {
        return $this->state(fn (array $attributes): array => [
            'title' => 'Home',
            'type' => NavigationLinkType::Home,
            'resource_id' => null,
            'url' => null,
        ]);
    }

    public function catalog(): static
    {
        return $this->state(fn (array $attributes): array => [
            'title' => 'All products',
            'type' => NavigationLinkType::Catalog,
            'resource_id' => null,
            'url' => null,
        ]);
    }

    public function customUrl(string $url = '/products'): static
    {
        return $this->state(fn (array $attributes): array => [
            'title' => 'Custom link',
            'type' => NavigationLinkType::Url,
            'resource_id' => null,
            'url' => $url,
        ]);
    }
}
