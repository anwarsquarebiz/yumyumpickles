<?php

use App\Models\User;

it('redirects guests to the login page', function () {
    $this->get('/admin')->assertRedirect('/login');
});

it('forbids customers from reaching the admin panel', function () {
    $this->actingAs(User::factory()->customer()->create());

    $this->get('/admin')->assertForbidden();
});

it('allows staff to reach the dashboard', function () {
    $this->actingAs(User::factory()->admin()->create());

    $this->get('/admin')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/dashboard')
            ->has('metrics')
        );
});

it('keeps a customer out of every admin section', function (string $path) {
    $this->actingAs(User::factory()->customer()->create());

    $this->get($path)->assertForbidden();
})->with([
    '/admin',
    '/admin/products',
    '/admin/collections',
    '/admin/coupons',
    '/admin/orders',
    '/admin/customers',
    '/admin/banners',
    '/admin/pages',
    '/admin/blogs',
    '/admin/settings',
    '/admin/navigation',
    '/admin/shipping-methods',
]);
