<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\ReorderProductsRequest;
use App\Http\Requests\Admin\Product\StoreProductRequest;
use App\Http\Requests\Admin\Product\UpdateProductRequest;
use App\Http\Resources\AdminProductResource;
use App\Http\Resources\ProductDetailResource;
use App\Models\Collection;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\Catalog\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly ProductRepositoryInterface $repository,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Product::class);

        $filters = [
            'search' => $request->string('search')->trim()->value() ?: null,
            'status' => $request->input('status'),
            'collection_id' => $request->integer('collection_id') ?: null,
            'sort' => $request->input('sort', 'custom'),
        ];

        return Inertia::render('admin/products/index', [
            'products' => AdminProductResource::collection($this->repository->paginateForAdmin($filters)),
            'filters' => $filters,
            'statuses' => ProductStatus::options(),
            'collections' => $this->collectionOptions(),
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Product::class);

        return Inertia::render('admin/products/create', [
            'statuses' => ProductStatus::options(),
            'inventoryPolicies' => InventoryPolicy::options(),
            'collections' => $this->collectionOptions(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = $this->products->create($request->validated());

        return to_route('admin.products.edit', $product)
            ->with('success', "Product \"{$product->title}\" was created.");
    }

    public function edit(Product $product): Response
    {
        $this->authorize('update', $product);

        $product->load(['variants', 'options', 'images', 'collections']);

        return Inertia::render('admin/products/edit', [
            'product' => new ProductDetailResource($product),
            'statuses' => ProductStatus::options(),
            'inventoryPolicies' => InventoryPolicy::options(),
            'collections' => $this->collectionOptions(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());

        return back()->with('success', 'Product saved.');
    }

    public function reorder(ReorderProductsRequest $request): RedirectResponse
    {
        $this->products->reorder($request->validated('ids'));

        return back();
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $title = $product->title;

        $this->products->delete($product);

        return to_route('admin.products.index')
            ->with('success', "Product \"{$title}\" was deleted.");
    }

    /**
     * Collections offered as checkboxes on the product form.
     *
     * @return array<int, array{value: int, label: string}>
     */
    private function collectionOptions(): array
    {
        return Collection::query()
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Collection $collection): array => [
                'value' => $collection->id,
                'label' => $collection->title,
            ])
            ->all();
    }
}
