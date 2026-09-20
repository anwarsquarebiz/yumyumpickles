<?php

use App\Models\User;
use App\Services\Ads\GoogleTagManagerSettings;

it('lets staff save a GTM container id', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', storeSettingsPayload([
            'gtm' => [
                'enabled' => true,
                'container_id' => 'gtm-abcdef1',
            ],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHas('success', 'Settings saved.');

    $gtm = app(GoogleTagManagerSettings::class);

    expect($gtm->enabled())->toBeTrue()
        ->and($gtm->containerId())->toBe('GTM-ABCDEF1');
});

it('shares the container id with the storefront and not with admin pages', function () {
    app(GoogleTagManagerSettings::class)->update([
        'enabled' => true,
        'container_id' => 'GTM-ABCDEF1',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('id="google-tag-manager"', false)
        ->assertSee('https://www.googletagmanager.com/gtm.js?id=', false)
        ->assertSee('GTM-ABCDEF1', false)
        ->assertSee('https://www.googletagmanager.com/ns.html?id=GTM-ABCDEF1', false)
        ->assertInertia(fn ($page) => $page
            ->where('google_tag_manager.container_id', 'GTM-ABCDEF1')
            ->where('google_tag_manager.enabled', true)
        );

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('google-tag-manager', false)
        ->assertDontSee('googletagmanager.com/gtm.js', false)
        ->assertInertia(fn ($page) => $page->where('google_tag_manager', null));
});

it('does not share google tag manager when it is disabled', function () {
    app(GoogleTagManagerSettings::class)->update([
        'enabled' => false,
        'container_id' => 'GTM-ABCDEF1',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('google_tag_manager', null));
});

it('keeps google tag manager when a payload omits the gtm key', function () {
    app(GoogleTagManagerSettings::class)->update([
        'enabled' => true,
        'container_id' => 'GTM-ABCDEF1',
    ]);

    $payload = storeSettingsPayload();
    unset($payload['gtm']);

    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', $payload)
        ->assertRedirect('/admin/settings');

    $gtm = app(GoogleTagManagerSettings::class);

    expect($gtm->enabled())->toBeTrue()
        ->and($gtm->containerId())->toBe('GTM-ABCDEF1');
});

it('rejects an invalid container id', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', storeSettingsPayload([
            'gtm' => ['container_id' => 'G-ABC123XYZ0'],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHasErrors('gtm.container_id');
});

it('includes google tag manager fields on the settings page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/settings/edit')
            ->where('google_tag_manager.enabled', false)
            ->where('google_tag_manager.container_id', '')
        );
});
