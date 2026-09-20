<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Settings\UpdateBrandingSettingsRequest;
use App\Http\Requests\Admin\Settings\UpdateStoreSettingsRequest;
use App\Services\Ads\GoogleAnalyticsSettings;
use App\Services\Ads\GoogleTagManagerSettings;
use App\Services\Ads\MetaAdsSettings;
use App\Services\Settings\BrandingService;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SettingsService $settings,
        private readonly BrandingService $branding,
        private readonly MetaAdsSettings $metaAds,
        private readonly GoogleAnalyticsSettings $googleAnalytics,
        private readonly GoogleTagManagerSettings $googleTagManager,
    ) {}

    public function edit(): Response
    {
        abort_unless(request()->user()?->isAdmin(), 403);

        return Inertia::render('admin/settings/edit', [
            'settings' => $this->settings->all()->except(['ads.meta.access_token']),
            'meta_ads' => $this->metaAds->adminPayload(),
            'google_analytics' => $this->googleAnalytics->adminPayload(),
            'google_tag_manager' => $this->googleTagManager->adminPayload(),
            'branding' => [
                'logo_url' => $this->branding->logoUrl(),
                'favicon_url' => $this->branding->faviconUrl(),
            ],
        ]);
    }

    public function update(UpdateStoreSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $this->settings->setMany([
            'store.name' => $data['store']['name'],
            'store.email' => $data['store']['email'],
            'store.phone' => $data['store']['phone'] ?? '',
            'store.address' => $data['store']['address'] ?? '',
            'store.tagline' => $data['store']['tagline'] ?? '',
            'store.whatsapp' => $data['store']['whatsapp'] ?? '',
        ], 'store');

        $announcements = collect(preg_split('/\r\n|\r|\n/', (string) ($data['storefront']['announcements'] ?? '')))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();

        $this->settings->set('storefront.announcements', $announcements, 'storefront');

        $contentJson = trim((string) ($data['storefront']['content_json'] ?? ''));

        if ($contentJson !== '') {
            $decoded = json_decode($contentJson, true);

            if (is_array($decoded)) {
                foreach (['story_steps', 'why_choose', 'videos', 'testimonials', 'instagram', 'faqs', 'social_proof', 'policies'] as $key) {
                    if (array_key_exists($key, $decoded)) {
                        $this->settings->set('storefront.'.$key, $decoded[$key], 'storefront');
                    }
                }
            }
        }

        $this->settings->setMany([
            'checkout.tax_rate_basis_points' => (int) ($data['checkout']['tax_rate_basis_points'] ?? 0),
            'checkout.guest_checkout_enabled' => (bool) ($data['checkout']['guest_checkout_enabled'] ?? true),
        ], 'checkout');

        $this->settings->setMany([
            'seo.default_title' => $data['seo']['default_title'] ?? '',
            'seo.default_description' => $data['seo']['default_description'] ?? '',
        ], 'seo');

        $this->settings->setMany([
            'social.facebook' => $data['social']['facebook'] ?? '',
            'social.instagram' => $data['social']['instagram'] ?? '',
            'social.twitter' => $data['social']['twitter'] ?? '',
        ], 'social');

        if (array_key_exists('ads', $data)) {
            $this->metaAds->update($data['ads']);
        }

        if (array_key_exists('google', $data)) {
            $this->googleAnalytics->update($data['google']);
        }

        if (array_key_exists('gtm', $data)) {
            $this->googleTagManager->update($data['gtm']);
        }

        return back()->with('success', 'Settings saved.');
    }

    public function updateBranding(UpdateBrandingSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->branding->update([
            'logo' => $request->file('logo'),
            'remove_logo' => $validated['remove_logo'] ?? false,
            'favicon' => $request->file('favicon'),
            'remove_favicon' => $validated['remove_favicon'] ?? false,
        ]);

        return back()->with('success', 'Branding saved.');
    }
}
