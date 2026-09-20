<?php

use App\Models\ShippingMethod;
use App\Models\User;

it('renders the shipping methods page for staff', function () {
    ShippingMethod::factory()->create(['name' => 'Standard shipping']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/shipping-methods')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/shipping-methods/index')
            ->has('shipping_methods', 1)
            ->has('shipping_types')
        );
});

it('adds a shipping method', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/admin/shipping-methods', [
            'name' => 'Express',
            'type' => 'flat_rate',
            'rate' => '9.99',
            'is_active' => true,
            'position' => 1,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Shipping method added.');

    expect(ShippingMethod::query()->where('name', 'Express')->exists())->toBeTrue();
});

it('removes a shipping method', function () {
    $method = ShippingMethod::factory()->create();

    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/shipping-methods')
        ->delete("/admin/shipping-methods/{$method->id}")
        ->assertRedirect('/admin/shipping-methods')
        ->assertSessionHas('success', 'Shipping method removed.');

    expect(ShippingMethod::query()->find($method->id))->toBeNull();
});
