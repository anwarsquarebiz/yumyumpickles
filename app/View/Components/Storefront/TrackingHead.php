<?php

namespace App\View\Components\Storefront;

use App\Services\Ads\GoogleAnalyticsSettings;
use App\Services\Ads\GoogleTagManagerSettings;
use App\Services\Ads\MetaAdsSettings;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TrackingHead extends Component
{
    public ?string $gtmContainerId = null;

    public ?string $gaMeasurementId = null;

    public ?string $metaPixelId = null;

    public function __construct(
        GoogleTagManagerSettings $gtm,
        GoogleAnalyticsSettings $ga,
        MetaAdsSettings $meta,
    ) {
        if (request()->routeIs('admin.*')) {
            return;
        }

        $this->gtmContainerId = $gtm->publicConfig()['container_id'] ?? null;
        $this->gaMeasurementId = $ga->publicConfig()['measurement_id'] ?? null;
        $this->metaPixelId = $meta->publicPixel()['pixel_id'] ?? null;
    }

    public function shouldRender(): bool
    {
        return $this->gtmContainerId !== null
            || $this->gaMeasurementId !== null
            || $this->metaPixelId !== null;
    }

    public function render(): View
    {
        return view('components.storefront.tracking-head');
    }
}
