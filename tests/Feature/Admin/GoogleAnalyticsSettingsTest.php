<?php

use App\Models\User;
use App\Services\Ads\GoogleAnalyticsSettings;

it('lets staff save a GA4 measurement id', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', storeSettingsPayload([
            'google' => [
                'enabled' => true,
                'measurement_id' => 'g-abc123xyz0',
            ],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHas('success', 'Settings saved.');

    $google = app(GoogleAnalyticsSettings::class);

    expect($google->enabled())->toBeTrue()
        ->and($google->measurementId())->toBe('G-ABC123XYZ0');
});

it('shares the measurement id with the storefront and not with admin pages', function () {
    app(GoogleAnalyticsSettings::class)->update([
        'enabled' => true,
        'measurement_id' => 'G-ABC123XYZ0',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('id="google-analytics-gtag"', false)
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC123XYZ0', false)
        ->assertInertia(fn ($page) => $page
            ->where('google_analytics.measurement_id', 'G-ABC123XYZ0')
            ->where('google_analytics.enabled', true)
        );

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin')
        ->assertOk()
        ->assertDontSee('google-analytics-gtag', false)
        ->assertDontSee('gtag/js?id=', false)
        ->assertInertia(fn ($page) => $page->where('google_analytics', null));
});

it('does not share google analytics when it is disabled', function () {
    app(GoogleAnalyticsSettings::class)->update([
        'enabled' => false,
        'measurement_id' => 'G-ABC123XYZ0',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('google_analytics', null));
});

it('keeps google analytics when a payload omits the google key', function () {
    app(GoogleAnalyticsSettings::class)->update([
        'enabled' => true,
        'measurement_id' => 'G-ABC123XYZ0',
    ]);

    $payload = storeSettingsPayload();
    unset($payload['google']);

    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', $payload)
        ->assertRedirect('/admin/settings');

    $google = app(GoogleAnalyticsSettings::class);

    expect($google->enabled())->toBeTrue()
        ->and($google->measurementId())->toBe('G-ABC123XYZ0');
});

it('accepts a google tag measurement id', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', storeSettingsPayload([
            'google' => [
                'enabled' => true,
                'measurement_id' => 'gt-wxyz987654',
            ],
        ]))
        ->assertRedirect('/admin/settings');

    expect(app(GoogleAnalyticsSettings::class)->measurementId())->toBe('GT-WXYZ987654');
});

it('rejects an invalid measurement id', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings', storeSettingsPayload([
            'google' => ['measurement_id' => 'UA-123456-1'],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHasErrors('google.measurement_id');
});

it('includes google analytics fields on the settings page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/settings/edit')
            ->where('google_analytics.enabled', false)
            ->where('google_analytics.measurement_id', '')
        );
});
