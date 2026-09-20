<?php

namespace App\Http\Middleware;

use App\Http\Resources\CartItemResource;
use App\Http\Resources\ProductSummaryResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Ads\GoogleAnalyticsSettings;
use App\Services\Ads\GoogleTagManagerSettings;
use App\Services\Ads\MetaAdsSettings;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use App\Services\Currency\CurrencyConverter;
use App\Services\Currency\CurrencyService;
use App\Services\Settings\BrandingService;
use App\Services\Settings\SettingsService;
use App\Services\Storefront\NavigationService;
use App\Services\Storefront\StorefrontContentService;
use App\Support\CartTotals;
use App\Support\Money;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(
        private readonly SettingsService $settings,
        private readonly BrandingService $branding,
        private readonly NavigationService $navigation,
        private readonly CartResolver $cartResolver,
        private readonly CartService $carts,
        private readonly CurrencyService $currencies,
        private readonly CurrencyConverter $converter,
        private readonly MetaAdsSettings $metaAds,
        private readonly GoogleAnalyticsSettings $googleAnalytics,
        private readonly GoogleTagManagerSettings $googleTagManager,
        private readonly StorefrontContentService $content,
        private readonly ProductRepositoryInterface $products,
    ) {}

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user === null ? null : [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'is_admin' => $user->isAdmin(),
                    'email_verified_at' => $user->email_verified_at,
                ],
            ],
            'store' => fn (): array => [
                'name' => $this->settings->get('store.name'),
                'email' => $this->settings->get('store.email'),
                'phone' => $this->settings->get('store.phone'),
                'currency' => $this->currencies->base(),
                'social' => $this->settings->group('social')->all(),
                'logo_url' => $this->branding->logoUrl(),
                'favicon_url' => $this->branding->faviconUrl(),
                'free_shipping_threshold' => $this->converter->present(
                    Money::fromMinor((int) config('shop.currency.free_shipping_threshold_amount', 5000)),
                ),
            ],
            'currency' => fn (): array => [
                'current' => $this->currencies->current(),
                'base' => $this->currencies->base(),
                'options' => $this->currencies->options(),
            ],
            'navigation' => fn (): array => [
                'header' => $this->navigation->header(),
                'collections' => $this->navigation->collections(),
                'pages' => $this->navigation->pages(),
            ],

            /*
             | A lazy closure so the header badge never costs a query on requests
             | that do not render it, and never creates a cart just by browsing.
             */
            'cart' => fn (): array => $this->cartSummary(),
            'content' => fn (): ?array => $this->isAdminRequest($request)
                ? null
                : $this->content->bundle(),
            'catalog' => fn (): array => $this->isAdminRequest($request)
                ? []
                : ProductSummaryResource::collection(
                    $this->products->paginatePublished([], 100)->getCollection()
                )->resolve(),
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'open_cart' => (bool) $request->session()->get('open_cart'),
            ],
            'meta_pixel' => fn (): ?array => $request->routeIs('admin.*')
                ? null
                : $this->metaAds->publicPixel(),
            'google_analytics' => fn (): ?array => $request->routeIs('admin.*')
                ? null
                : $this->googleAnalytics->publicConfig(),
            'google_tag_manager' => fn (): ?array => $request->routeIs('admin.*')
                ? null
                : $this->googleTagManager->publicConfig(),
        ];
    }

    /**
     * Header badge plus line items for the storefront cart drawer.
     *
     * @return array<string, mixed>
     */
    private function cartSummary(): array
    {
        $cart = $this->cartResolver->current();
        $totals = $cart === null ? CartTotals::empty() : $this->carts->totals($cart);
        $presented = $this->converter->presentTotals($totals);

        return [
            'item_count' => $presented['item_count'],
            'subtotal' => $presented['subtotal'],
            'discount' => $presented['discount'],
            'total' => $presented['total'],
            'coupon_code' => $presented['coupon_code'],
            'items' => $cart === null
                ? []
                : CartItemResource::collection($cart->items->sortBy('id')->values())->resolve(),
        ];
    }

    private function isAdminRequest(Request $request): bool
    {
        return $request->routeIs('admin.*', 'login', 'register', 'password.*', 'verification.*');
    }
}
