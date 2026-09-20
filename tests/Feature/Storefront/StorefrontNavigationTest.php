<?php

use App\Enums\NavigationLinkType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\Page;

it('falls back to catalogue and collection links when the header menu is empty', function () {
    Collection::factory()->create(['title' => 'Wellness', 'position' => 1]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('navigation.header.0.title', 'All products')
            ->where('navigation.header.1.title', 'Wellness')
        );
});

it('renders configured header links on the storefront', function () {
    $page = Page::factory()->create(['title' => 'About Us', 'slug' => 'about-us']);

    NavigationItem::factory()->catalog()->create(['position' => 1]);
    NavigationItem::factory()->create([
        'title' => 'About',
        'type' => NavigationLinkType::Page,
        'resource_id' => $page->id,
        'position' => 2,
    ]);
    NavigationItem::factory()->customUrl('https://example.com/help')->create([
        'title' => 'Help',
        'position' => 3,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('navigation.header', 3)
            ->where('navigation.header.0.title', 'All products')
            ->where('navigation.header.1.title', 'About')
            ->where('navigation.header.2.title', 'Help')
            ->where('navigation.header.2.external', true)
        );
});

it('omits unpublished collections from the header', function () {
    $live = Collection::factory()->create(['title' => 'Live']);
    $draft = Collection::factory()->draft()->create(['title' => 'Hidden']);

    NavigationItem::factory()->create([
        'title' => 'Live',
        'type' => NavigationLinkType::Collection,
        'resource_id' => $live->id,
        'position' => 1,
    ]);
    NavigationItem::factory()->create([
        'title' => 'Hidden',
        'type' => NavigationLinkType::Collection,
        'resource_id' => $draft->id,
        'position' => 2,
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('navigation.header', 1)
            ->where('navigation.header.0.title', 'Live')
        );
});
