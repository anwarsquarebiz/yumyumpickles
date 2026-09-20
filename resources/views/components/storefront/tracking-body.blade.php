@if ($gtmContainerId)
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id={{ $gtmContainerId }}"
    height="0" width="0" style="display:none;visibility:hidden" title="Google Tag Manager"></iframe></noscript>
@endif
@if ($metaPixelId)
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1"
    alt=""></noscript>
@endif
