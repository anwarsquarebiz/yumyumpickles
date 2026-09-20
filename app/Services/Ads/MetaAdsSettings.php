<?php

namespace App\Services\Ads;

use App\Services\Settings\SettingsService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Merchant-facing Meta Pixel / Conversions API credentials.
 *
 * The access token is stored encrypted in the settings table so it can be
 * rotated from admin without a deploy, and is never returned to Inertia.
 */
class MetaAdsSettings
{
    public function __construct(private readonly SettingsService $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('ads.meta.enabled', false);
    }

    public function pixelId(): string
    {
        return trim((string) $this->settings->get('ads.meta.pixel_id', ''));
    }

    public function testEventCode(): string
    {
        return trim((string) $this->settings->get('ads.meta.test_event_code', ''));
    }

    public function advancedMatching(): bool
    {
        return (bool) $this->settings->get('ads.meta.advanced_matching', true);
    }

    public function accessToken(): ?string
    {
        $stored = (string) $this->settings->get('ads.meta.access_token', '');

        if ($stored === '') {
            return null;
        }

        try {
            $token = Crypt::decryptString($stored);
        } catch (DecryptException) {
            return null;
        }

        return $token === '' ? null : $token;
    }

    public function isConfigured(): bool
    {
        return $this->enabled() && $this->pixelId() !== '' && $this->accessToken() !== null;
    }

    /**
     * Pixel snippet config for the storefront. Null when tracking should not run.
     *
     * @return array{enabled: true, pixel_id: string}|null
     */
    public function publicPixel(): ?array
    {
        if (! $this->enabled() || $this->pixelId() === '') {
            return null;
        }

        return [
            'enabled' => true,
            'pixel_id' => $this->pixelId(),
        ];
    }

    /**
     * Admin form state. Never includes the raw access token.
     *
     * @return array{
     *     enabled: bool,
     *     pixel_id: string,
     *     test_event_code: string,
     *     advanced_matching: bool,
     *     has_access_token: bool
     * }
     */
    public function adminPayload(): array
    {
        return [
            'enabled' => $this->enabled(),
            'pixel_id' => $this->pixelId(),
            'test_event_code' => $this->testEventCode(),
            'advanced_matching' => $this->advancedMatching(),
            'has_access_token' => $this->accessToken() !== null,
        ];
    }

    /**
     * @param  array{
     *     enabled?: mixed,
     *     pixel_id?: mixed,
     *     access_token?: mixed,
     *     test_event_code?: mixed,
     *     advanced_matching?: mixed
     * }  $payload
     */
    public function update(array $payload): void
    {
        $values = [
            'ads.meta.enabled' => (bool) ($payload['enabled'] ?? false),
            'ads.meta.pixel_id' => trim((string) ($payload['pixel_id'] ?? '')),
            'ads.meta.test_event_code' => trim((string) ($payload['test_event_code'] ?? '')),
            'ads.meta.advanced_matching' => (bool) ($payload['advanced_matching'] ?? false),
        ];

        $token = trim((string) ($payload['access_token'] ?? ''));

        if ($token !== '') {
            $values['ads.meta.access_token'] = Crypt::encryptString($token);
        }

        $this->settings->setMany($values, 'ads');
    }
}
