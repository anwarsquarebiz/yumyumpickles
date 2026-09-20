<?php

namespace App\Services\Ads;

use App\Services\Settings\SettingsService;

/**
 * Merchant-facing Google Analytics 4 measurement ID.
 *
 * The ID is public (it appears in the page source) so it is stored in settings
 * rather than encrypted secrets.
 */
class GoogleAnalyticsSettings
{
    public function __construct(private readonly SettingsService $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('ads.google.enabled', false);
    }

    public function measurementId(): string
    {
        return strtoupper(trim((string) $this->settings->get('ads.google.measurement_id', '')));
    }

    /**
     * gtag config for the storefront. Null when tracking should not run.
     *
     * @return array{enabled: true, measurement_id: string}|null
     */
    public function publicConfig(): ?array
    {
        if (! $this->enabled() || $this->measurementId() === '') {
            return null;
        }

        return [
            'enabled' => true,
            'measurement_id' => $this->measurementId(),
        ];
    }

    /**
     * @return array{enabled: bool, measurement_id: string}
     */
    public function adminPayload(): array
    {
        return [
            'enabled' => $this->enabled(),
            'measurement_id' => $this->measurementId(),
        ];
    }

    /**
     * @param  array{enabled?: mixed, measurement_id?: mixed}  $payload
     */
    public function update(array $payload): void
    {
        $this->settings->setMany([
            'ads.google.enabled' => (bool) ($payload['enabled'] ?? false),
            'ads.google.measurement_id' => strtoupper(trim((string) ($payload['measurement_id'] ?? ''))),
        ], 'ads');
    }
}
