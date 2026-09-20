<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\BannerResource;
use App\Http\Resources\CollectionSummaryResource;
use App\Http\Resources\ProductSummaryResource;
use App\Models\Banner;
use App\Models\Collection;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Settings\SettingsService;
use App\Support\CacheKeys;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $products,
        private readonly SettingsService $settings,
    ) {}

    public function __invoke(): Response
    {
        return Inertia::render('storefront/home', [
            'banners' => BannerResource::collection(
                CacheKeys::remember(CacheKeys::Banners, 'home', fn () => Banner::query()->live()->get())
            ),
            'featuredProducts' => ProductSummaryResource::collection(
                $this->products->publishedBySlugs(
                    config('shop.catalog.home_featured_slugs', [])
                )
            ),
            'collections' => CollectionSummaryResource::collection(
                Collection::query()
                    ->published()
                    ->orderBy('position')
                    ->orderBy('title')
                    ->limit(6)
                    ->get()
            ),
            'seo' => [
                'title' => $this->settings->get('seo.default_title'),
                'description' => $this->settings->get('seo.default_description'),
            ],
        ]);
    }
}
