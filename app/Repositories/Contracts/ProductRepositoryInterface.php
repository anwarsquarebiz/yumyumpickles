<?php

namespace App\Repositories\Contracts;

use App\Models\Collection as ProductCollection;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Read-side queries for the catalog.
 *
 * @phpstan-type CatalogFilters array{
 *     search?: string|null,
 *     sort?: string|null,
 *     in_stock?: bool|null,
 *     min_price?: string|int|null,
 *     max_price?: string|int|null
 * }
 */
interface ProductRepositoryInterface
{
    /**
     * Storefront listing of every published product.
     *
     * @param  CatalogFilters  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginatePublished(array $filters = [], ?int $perPage = null): LengthAwarePaginator;

    /**
     * Storefront listing scoped to one collection.
     *
     * @param  CatalogFilters  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForCollection(
        ProductCollection $collection,
        array $filters = [],
        ?int $perPage = null,
    ): LengthAwarePaginator;

    /**
     * A published product with everything the detail page renders, or null.
     */
    public function findPublishedBySlug(string $slug): ?Product;

    /**
     * Published products in the given slug order, for merchandised surfaces
     * such as the home page. Missing or unpublished slugs are skipped.
     *
     * @param  list<string>  $slugs
     * @return EloquentCollection<int, Product>
     */
    public function publishedBySlugs(array $slugs): EloquentCollection;

    /**
     * Admin listing, which includes drafts and archived products.
     *
     * @param  array{search?: string|null, status?: string|null, collection_id?: int|null, sort?: string|null}  $filters
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(array $filters = [], ?int $perPage = null): LengthAwarePaginator;
}
