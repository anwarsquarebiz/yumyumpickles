<?php

namespace App\View\Components\Storefront;

use App\Services\Ads\GoogleTagManagerSettings;
use App\Services\Ads\MetaAdsSettings;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class TrackingBody extends Component
{
    public ?string $gtmContainerId = null;

    public ?string $metaPixelId = null;

    public function __construct(
        GoogleTagManagerSettings $gtm,
        MetaAdsSettings $meta,
    ) {
        if (request()->routeIs('admin.*')) {
            return;
        }

        $this->gtmContainerId = $gtm->publicConfig()['container_id'] ?? null;
        $this->metaPixelId = $meta->publicPixel()['pixel_id'] ?? null;
    }

    public function shouldRender(): bool
    {
        return $this->gtmContainerId !== null || $this->metaPixelId !== null;
    }

    public function render(): View
    {
        return view('components.storefront.tracking-body');
    }
}
