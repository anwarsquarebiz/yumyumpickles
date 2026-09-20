<?php

namespace App\Services\Ads;

use App\Services\Settings\SettingsService;

/**
 * Merchant-facing Google Tag Manager container ID.
 *
 * The ID is public (it appears in the page source) so it is stored in settings
 * rather than encrypted secrets.
 */
class GoogleTagManagerSettings
{
    public function __construct(private readonly SettingsService $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('ads.gtm.enabled', false);
    }

    public function containerId(): string
    {
        return strtoupper(trim((string) $this->settings->get('ads.gtm.container_id', '')));
    }

    /**
     * GTM config for the storefront. Null when the container should not load.
     *
     * @return array{enabled: true, container_id: string}|null
     */
    public function publicConfig(): ?array
    {
        if (! $this->enabled() || $this->containerId() === '') {
            return null;
        }

        return [
            'enabled' => true,
            'container_id' => $this->containerId(),
        ];
    }

    /**
     * @return array{enabled: bool, container_id: string}
     */
    public function adminPayload(): array
    {
        return [
            'enabled' => $this->enabled(),
            'container_id' => $this->containerId(),
        ];
    }

    /**
     * @param  array{enabled?: mixed, container_id?: mixed}  $payload
     */
    public function update(array $payload): void
    {
        $this->settings->setMany([
            'ads.gtm.enabled' => (bool) ($payload['enabled'] ?? false),
            'ads.gtm.container_id' => strtoupper(trim((string) ($payload['container_id'] ?? ''))),
        ], 'ads');
    }
}
