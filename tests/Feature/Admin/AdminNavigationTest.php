<?php

use App\Enums\NavigationLinkType;
use App\Models\Collection;
use App\Models\NavigationItem;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('lists header menu items for staff', function () {
    NavigationItem::factory()->catalog()->create(['title' => 'Shop']);

    $this->actingAs($this->admin)
        ->get('/admin/navigation')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/navigation/index')
            ->has('items.data', 1)
            ->where('items.data.0.title', 'Shop')
            ->has('link_types')
            ->has('collections')
            ->has('pages')
            ->has('blogs')
        );
});

it('adds a catalog link to the header', function () {
    $this->actingAs($this->admin)
        ->post('/admin/navigation', [
            'title' => 'All products',
            'type' => NavigationLinkType::Catalog->value,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $item = NavigationItem::query()->firstOrFail();

    expect($item->title)->toBe('All products')
        ->and($item->type)->toBe(NavigationLinkType::Catalog)
        ->and($item->position)->toBe(1);
});

it('adds a collection link', function () {
    $collection = Collection::factory()->create(['title' => 'Pain relief']);

    $this->actingAs($this->admin)
        ->post('/admin/navigation', [
            'title' => 'Pain relief',
            'type' => NavigationLinkType::Collection->value,
            'resource_id' => $collection->id,
        ])
        ->assertRedirect();

    expect(NavigationItem::query()->firstOrFail()->resource_id)->toBe($collection->id);
});

it('requires a resource when linking to a collection', function () {
    $this->actingAs($this->admin)
        ->post('/admin/navigation', [
            'title' => 'Pain relief',
            'type' => NavigationLinkType::Collection->value,
        ])
        ->assertSessionHasErrors('resource_id');
});

it('rejects unsafe custom urls', function () {
    $this->actingAs($this->admin)
        ->post('/admin/navigation', [
            'title' => 'Bad',
            'type' => NavigationLinkType::Url->value,
            'url' => 'javascript:alert(1)',
        ])
        ->assertSessionHasErrors('url');
});

it('updates a menu item', function () {
    $item = NavigationItem::factory()->catalog()->create(['title' => 'Old']);

    $this->actingAs($this->admin)
        ->put("/admin/navigation/{$item->id}", [
            'title' => 'Catalogue',
            'type' => NavigationLinkType::Catalog->value,
            'position' => 3,
        ])
        ->assertRedirect();

    expect($item->fresh()->title)->toBe('Catalogue')
        ->and($item->fresh()->position)->toBe(3);
});

it('removes a menu item', function () {
    $item = NavigationItem::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/navigation/{$item->id}")
        ->assertRedirect();

    expect(NavigationItem::query()->count())->toBe(0);
});

it('reorders header menu items', function () {
    $first = NavigationItem::factory()->catalog()->create(['title' => 'Shop', 'position' => 1]);
    $second = NavigationItem::factory()->home()->create(['title' => 'Home', 'position' => 2]);
    $third = NavigationItem::factory()->customUrl('/blogs/news')->create(['title' => 'Journal', 'position' => 3]);

    $this->actingAs($this->admin)
        ->put('/admin/navigation/order', [
            'ids' => [$third->id, $first->id, $second->id],
        ])
        ->assertRedirect();

    expect($third->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2)
        ->and($second->fresh()->position)->toBe(3);
});

it('keeps omitted items at the end when reordering', function () {
    $first = NavigationItem::factory()->catalog()->create(['position' => 1]);
    $second = NavigationItem::factory()->home()->create(['position' => 2]);
    $third = NavigationItem::factory()->customUrl()->create(['position' => 3]);

    $this->actingAs($this->admin)
        ->put('/admin/navigation/order', [
            'ids' => [$third->id, $first->id],
        ])
        ->assertRedirect();

    expect($third->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2)
        ->and($second->fresh()->position)->toBe(3);
});

it('rejects unknown ids when reordering', function () {
    NavigationItem::factory()->catalog()->create();

    $this->actingAs($this->admin)
        ->put('/admin/navigation/order', [
            'ids' => [999_999],
        ])
        ->assertSessionHasErrors('ids.0');
});

it('keeps customers out of navigation', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get('/admin/navigation')
        ->assertForbidden();
});

it('keeps customers from reordering navigation', function () {
    $item = NavigationItem::factory()->catalog()->create();

    $this->actingAs(User::factory()->customer()->create())
        ->put('/admin/navigation/order', [
            'ids' => [$item->id],
        ])
        ->assertForbidden();
});
