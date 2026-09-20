<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ProductIndexRequest;
use App\Http\Resources\ProductDetailResource;
use App\Http\Resources\ProductSummaryResource;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function index(ProductIndexRequest $request): Response
    {
        return Inertia::render('storefront/products/index', [
            'products' => ProductSummaryResource::collection(
                $this->products->paginatePublished($request->catalogFilters(), 100)
            ),
            'filters' => $request->filters(),
            'seo' => [
                'title' => 'Shop Homemade Pickles',
                'description' => 'Explore homemade prawn, brinjal, bombil, tendli and gift-set pickles from YumYum.',
            ],
        ]);
    }

    public function show(string $slug): Response
    {
        $product = $this->products->findPublishedBySlug($slug);

        abort_if($product === null, 404);

        $product->load([
            'reviews' => fn ($query) => $query->approved()->latest('reviewed_at')->limit(8),
        ]);

        $relatedSlugs = array_values(array_filter(
            $product->metadata['pairs_with'] ?? [],
            fn ($item): bool => is_string($item) && $item !== $product->slug,
        ));

        return Inertia::render('storefront/products/show', [
            'product' => new ProductDetailResource($product),
            'related' => ProductSummaryResource::collection($this->products->publishedBySlugs($relatedSlugs)),
            'seo' => [
                'title' => $product->metaTitle(),
                'description' => $product->metaDescription(),
            ],
        ]);
    }
}
