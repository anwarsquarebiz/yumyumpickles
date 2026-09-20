<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\StorefrontProductResource;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function products(Request $request): JsonResponse
    {
        $query = Product::query()
            ->published()
            ->with(['variants', 'images', 'collections', 'reviews'])
            ->withCount(['reviews as approved_reviews_count' => fn ($reviews) => $reviews->approved()])
            ->orderBy('position');

        if ($request->filled('category')) {
            $category = $request->string('category')->toString();
            $query->whereHas('collections', function ($collections) use ($category): void {
                $collections->where('title', $category)->orWhere('slug', $category);
            });
        }

        if ($request->filled('q')) {
            $query->search($request->string('q')->toString());
        }

        if ($request->boolean('inStock')) {
            $query->whereHas('variants', fn ($variants) => $variants->inStock());
        }

        $products = $query->get();

        if ($request->filled('spice')) {
            $spice = $request->string('spice')->toString();
            $products = $products->filter(
                fn (Product $product): bool => ($product->metadata['spice'] ?? '') === $spice
            )->values();
        }

        return response()->json([
            'data' => StorefrontProductResource::collection($products),
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $product = Product::query()
            ->published()
            ->with(['variants', 'images', 'collections', 'reviews'])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => new StorefrontProductResource($product),
        ]);
    }

    public function collections(): JsonResponse
    {
        $collections = Collection::query()->published()->orderBy('position')->get();

        return response()->json([
            'data' => $collections->map(fn (Collection $collection): array => [
                'name' => $collection->title,
                'slug' => $collection->slug,
                'label' => $collection->title,
                'tagline' => $collection->description,
                'image' => $collection->imageUrl(),
                'position' => 'center',
            ]),
        ]);
    }
}
