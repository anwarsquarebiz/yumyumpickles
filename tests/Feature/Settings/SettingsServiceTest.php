<?php

use App\Models\Setting;
use App\Services\Settings\SettingsService;

beforeEach(function () {
    $this->settings = app(SettingsService::class);
});

it('falls back to a default when a key has never been written', function () {
    expect($this->settings->get('store.currency'))->toBe('USD');
});

it('returns the given fallback for an unknown key', function () {
    expect($this->settings->get('nothing.here', 'fallback'))->toBe('fallback');
});

it('persists and reads back a scalar', function () {
    $this->settings->set('store.phone', '+1 555 0100');

    expect($this->settings->get('store.phone'))->toBe('+1 555 0100');
});

it('persists a boolean and an integer without stringifying them', function () {
    $this->settings->set('checkout.guest_checkout_enabled', false);
    $this->settings->set('checkout.tax_rate_basis_points', 875);

    expect($this->settings->get('checkout.guest_checkout_enabled'))->toBeFalse()
        ->and($this->settings->get('checkout.tax_rate_basis_points'))->toBe(875);
});

it('persists an array', function () {
    $this->settings->set('shipping.countries', ['US', 'CA']);

    expect($this->settings->get('shipping.countries'))->toBe(['US', 'CA']);
});

it('overwrites rather than duplicating an existing key', function () {
    $this->settings->set('store.name', 'First');
    $this->settings->set('store.name', 'Second');

    expect(Setting::query()->where('key', 'store.name')->count())->toBe(1)
        ->and($this->settings->get('store.name'))->toBe('Second');
});

it('writes many settings at once', function () {
    $this->settings->setMany([
        'social.facebook' => 'https://facebook.test/shop',
        'social.instagram' => 'https://instagram.test/shop',
    ], 'social');

    expect($this->settings->get('social.facebook'))->toBe('https://facebook.test/shop')
        ->and($this->settings->get('social.instagram'))->toBe('https://instagram.test/shop');
});

it('returns a group of settings by prefix', function () {
    $this->settings->set('social.twitter', 'https://x.test/shop', 'social');

    $social = $this->settings->group('social');

    expect($social->keys()->all())->toContain('social.twitter')
        ->and($social->keys()->every(fn (string $key): bool => str_starts_with($key, 'social.')))->toBeTrue();
});
