<?php

use App\Models\User;
use App\Services\Settings\SettingsService;

it('lets staff save social links and shares them with the storefront', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', storeSettingsPayload([
            'social' => [
                'facebook' => 'https://facebook.com/yumyum',
                'instagram' => 'https://www.instagram.com/yumyum/',
                'youtube' => 'https://youtube.com/@yumyum',
                'twitter' => 'https://x.com/yumyum',
            ],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHas('success', 'Settings saved.');

    $settings = app(SettingsService::class);

    expect($settings->get('social.facebook'))->toBe('https://facebook.com/yumyum')
        ->and($settings->get('social.instagram'))->toBe('https://www.instagram.com/yumyum/')
        ->and($settings->get('social.youtube'))->toBe('https://youtube.com/@yumyum')
        ->and($settings->get('social.twitter'))->toBe('https://x.com/yumyum');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('content.brand.instagram', 'https://www.instagram.com/yumyum/')
            ->where('content.brand.facebook', 'https://facebook.com/yumyum')
            ->where('content.brand.youtube', 'https://youtube.com/@yumyum')
        );
});

it('rejects social links that are not urls', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', storeSettingsPayload([
            'social' => ['instagram' => 'yumyumhomemade_pickle'],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHasErrors('social.instagram');
});

it('includes social links on the settings page', function () {
    app(SettingsService::class)->set('social.youtube', 'https://youtube.com/@yumyum', 'social');

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/settings/edit')
            ->where('settings', fn ($settings): bool => $settings['social.youtube'] === 'https://youtube.com/@yumyum')
        );
});
