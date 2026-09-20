<?php

use App\Models\User;
use App\Services\Settings\BrandingService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('renders branding fields on the settings page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/settings/edit')
            ->has('branding', fn ($branding) => $branding
                ->where('logo_url', null)
                ->where('favicon_url', null)
            )
        );
});

it('uploads a logo and favicon', function () {
    $logo = UploadedFile::fake()->image('logo.png');
    $favicon = UploadedFile::fake()->image('favicon.png', 32, 32);

    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->post('/admin/settings/branding', [
            'logo' => $logo,
            'favicon' => $favicon,
        ])
        ->assertRedirect('/admin/settings')
        ->assertSessionHas('success', 'Branding saved.');

    $settings = app(SettingsService::class);

    expect($settings->get('brand.logo_path'))->not->toBeNull()
        ->and($settings->get('brand.favicon_path'))->not->toBeNull()
        ->and(app(BrandingService::class)->logoUrl())->not->toBeNull()
        ->and(app(BrandingService::class)->faviconUrl())->not->toBeNull();
});

it('shares uploaded branding with the storefront', function () {
    $logo = UploadedFile::fake()->image('logo.png');

    $this->actingAs(User::factory()->admin()->create())
        ->post('/admin/settings/branding', ['logo' => $logo]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('store.logo_url', app(BrandingService::class)->logoUrl())
        );
});

it('removes an uploaded logo', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/admin/settings/branding', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ]);

    $this->from('/admin/settings')
        ->post('/admin/settings/branding', ['remove_logo' => true])
        ->assertRedirect('/admin/settings');

    expect(app(SettingsService::class)->get('brand.logo_path'))->toBeNull()
        ->and(app(BrandingService::class)->logoUrl())->toBeNull();
});
