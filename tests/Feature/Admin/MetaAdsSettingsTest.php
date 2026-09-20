<?php

use App\Models\User;
use App\Services\Ads\MetaAdsSettings;
use App\Services\Settings\SettingsService;
use Illuminate\Support\Facades\Crypt;

it('redirects guests away from meta ads settings', function () {
    $this->get('/admin/settings')->assertRedirect('/login');
});

it('forbids customers from viewing meta ads settings', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get('/admin/settings')
        ->assertForbidden();
});

it('lets staff save a pixel id and encrypted access token', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->from('/admin/settings')
        ->put('/admin/settings', storeSettingsPayload([
            'ads' => [
                'enabled' => true,
                'pixel_id' => '123456789012345',
                'access_token' => 'meta-secret-token-xyz',
                'test_event_code' => 'TEST12345',
                'advanced_matching' => true,
            ],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHas('success', 'Settings saved.');

    $settings = app(SettingsService::class);
    $meta = app(MetaAdsSettings::class);

    expect($settings->get('ads.meta.enabled'))->toBeTrue()
        ->and($settings->get('ads.meta.pixel_id'))->toBe('123456789012345')
        ->and($settings->get('ads.meta.access_token'))->not->toBe('meta-secret-token-xyz')
        ->and($settings->get('ads.meta.access_token'))->not->toBe('')
        ->and(Crypt::decryptString((string) $settings->get('ads.meta.access_token')))->toBe('meta-secret-token-xyz')
        ->and($meta->accessToken())->toBe('meta-secret-token-xyz')
        ->and($meta->testEventCode())->toBe('TEST12345');
});

it('does not send the access token back to the admin page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put('/admin/settings', storeSettingsPayload([
        'ads' => [
            'enabled' => true,
            'pixel_id' => '123456789012345',
            'access_token' => 'meta-secret-token-xyz',
        ],
    ]));

    $this->get('/admin/settings')
        ->assertOk()
        ->assertInertia(function ($page): void {
            $props = $page->toArray()['props'];

            expect($props['settings'])->not->toHaveKey('ads.meta.access_token')
                ->and(json_encode($props))->not->toContain('meta-secret-token-xyz')
                ->and($props['meta_ads']['has_access_token'])->toBeTrue()
                ->and($props['meta_ads']['pixel_id'])->toBe('123456789012345')
                ->and($props['meta_ads']['enabled'])->toBeTrue()
                ->and($props['meta_pixel'] ?? null)->toBeNull();
        });
});

it('keeps the saved token when the access token field is left blank', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put('/admin/settings', storeSettingsPayload([
        'ads' => [
            'enabled' => true,
            'pixel_id' => '123456789012345',
            'access_token' => 'meta-secret-token-xyz',
        ],
    ]));

    $this->actingAs($admin)->put('/admin/settings', storeSettingsPayload([
        'ads' => [
            'enabled' => true,
            'pixel_id' => '123456789012345',
            'access_token' => '',
            'test_event_code' => 'KEEP',
        ],
    ]));

    expect(app(MetaAdsSettings::class)->accessToken())->toBe('meta-secret-token-xyz')
        ->and(app(MetaAdsSettings::class)->testEventCode())->toBe('KEEP');
});

it('rejects a non-numeric pixel id', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', storeSettingsPayload([
            'ads' => ['pixel_id' => 'not-a-pixel'],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHasErrors('ads.pixel_id');
});

it('shares the pixel id with the storefront and not with admin pages', function () {
    app(MetaAdsSettings::class)->update([
        'enabled' => true,
        'pixel_id' => '123456789012345',
        'access_token' => 'meta-secret-token-xyz',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('id="meta-pixel-sdk"', false)
        ->assertSee('https://connect.facebook.net/en_US/fbevents.js', false)
        ->assertSee('123456789012345', false)
        ->assertInertia(fn ($page) => $page
            ->where('meta_pixel.pixel_id', '123456789012345')
            ->where('meta_pixel.enabled', true)
        );

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('meta-pixel-sdk', false)
        ->assertDontSee('fbevents.js', false)
        ->assertInertia(fn ($page) => $page->where('meta_pixel', null));
});
