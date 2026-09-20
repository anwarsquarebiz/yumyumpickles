<?php

namespace App\Repositories;

use App\Models\Collection as ProductCollection;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Support\Money;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Relations every product card needs. Loaded eagerly because
     * Model::preventLazyLoading() is enabled outside production.
     *
     * @var array<int, string>
     */
    private const CardRelations = ['variants', 'images'];

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginatePublished(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        return $this->storefrontQuery($filters)
            ->paginate($perPage ?? (int) config('shop.catalog.products_per_page', 12))
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForCollection(
        ProductCollection $collection,
        array $filters = [],
        ?int $perPage = null,
    ): LengthAwarePaginator {
        return $this->storefrontQuery($filters)
            ->whereHas('collections', fn (Builder $query) => $query->whereKey($collection->getKey()))
            ->paginate($perPage ?? (int) config('shop.catalog.products_per_page', 12))
            ->withQueryString();
    }

    public function findPublishedBySlug(string $slug): ?Product
    {
        return Product::query()
            ->published()
            ->with(['variants', 'options', 'images', 'collections'])
            ->where('slug', $slug)
            ->first();
    }

    /**
     * @param  list<string>  $slugs
     * @return EloquentCollection<int, Product>
     */
    public function publishedBySlugs(array $slugs): EloquentCollection
    {
        if ($slugs === []) {
            return new EloquentCollection;
        }

        $products = Product::query()
            ->published()
            ->with(self::CardRelations)
            ->withMin('variants as min_price_amount', 'price_amount')
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');

        $ordered = new EloquentCollection;

        foreach ($slugs as $slug) {
            $product = $products->get($slug);

            if ($product !== null) {
                $ordered->push($product);
            }
        }

        return $ordered;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(array $filters = [], ?int $perPage = null): LengthAwarePaginator
    {
        $query = Product::query()
            ->with(['variants', 'images'])
            ->withCount('variants')
            ->withMin('variants as min_price_amount', 'price_amount')
            ->withSum('variants as total_inventory', 'inventory_quantity')
            ->search($filters['search'] ?? null);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['collection_id'])) {
            $query->whereHas('collections', fn (Builder $q) => $q->whereKey($filters['collection_id']));
        }

        $sort = $filters['sort'] ?? 'custom';

        /*
         | Custom order is one list so a product can be dragged from the end
         | of the catalog to the top. Other sorts keep the usual page size.
         */
        if ($perPage === null) {
            $perPage = $sort === 'custom'
                ? max((clone $query)->count(), 1)
                : (int) config('shop.catalog.admin_per_page', 20);
        }

        return $this->applySort($query, $sort)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Product>
     */
    private function storefrontQuery(array $filters): Builder
    {
        $query = Product::query()
            ->published()
            ->with(self::CardRelations)
            ->withMin('variants as min_price_amount', 'price_amount')
            ->search($filters['search'] ?? null);

        if (! empty($filters['in_stock'])) {
            $query->whereHas('variants', fn (Builder $variants) => $variants->inStock());
        }

        if (isset($filters['min_price']) && $filters['min_price'] !== '') {
            $query->whereHas(
                'variants',
                fn (Builder $variants) => $variants->where('price_amount', '>=', Money::fromDecimal($filters['min_price'])->amount)
            );
        }

        if (isset($filters['max_price']) && $filters['max_price'] !== '') {
            $query->whereHas(
                'variants',
                fn (Builder $variants) => $variants->where('price_amount', '<=', Money::fromDecimal($filters['max_price'])->amount)
            );
        }

        return $this->applySort($query, $filters['sort'] ?? 'custom');
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function applySort(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'price_asc' => $query->orderBy('min_price_amount')->orderBy('id'),
            'price_desc' => $query->orderByDesc('min_price_amount')->orderBy('id'),
            'title_asc' => $query->orderBy('title')->orderBy('id'),
            'title_desc' => $query->orderByDesc('title')->orderBy('id'),
            'oldest' => $query->oldest('created_at')->orderBy('id'),
            'newest' => $query->latest('created_at')->orderBy('id'),
            default => $query->orderBy('position')->orderBy('id'),
        };
    }
}
