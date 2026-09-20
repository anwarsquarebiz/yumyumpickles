<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ProductIndexRequest;
use App\Http\Resources\CollectionResource;
use App\Http\Resources\CollectionSummaryResource;
use App\Http\Resources\ProductSummaryResource;
use App\Models\Collection;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function __construct(private readonly ProductRepositoryInterface $products) {}

    public function index(): Response
    {
        $collections = Collection::query()
            ->published()
            ->withCount(['products' => fn ($query) => $query->published()])
            ->orderBy('position')
            ->orderBy('title')
            ->get();

        return Inertia::render('storefront/collections/index', [
            'collections' => CollectionResource::collection($collections),
            'seo' => [
                'title' => 'Collections',
                'description' => 'Shop our curated collections.',
            ],
        ]);
    }

    public function show(string $slug, ProductIndexRequest $request): Response
    {
        $collection = Collection::query()
            ->published()
            ->where('slug', $slug)
            ->first();

        abort_if($collection === null, 404);

        return Inertia::render('storefront/collections/show', [
            'collection' => new CollectionSummaryResource($collection),
            'description' => $collection->description,
            'products' => ProductSummaryResource::collection(
                $this->products->paginateForCollection($collection, $request->catalogFilters())
            ),
            'filters' => $request->filters(),
            'seo' => [
                'title' => $collection->metaTitle(),
                'description' => $collection->metaDescription(),
            ],
        ]);
    }
}
