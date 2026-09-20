<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Collection\StoreCollectionRequest;
use App\Http\Requests\Admin\Collection\UpdateCollectionRequest;
use App\Http\Resources\CollectionResource;
use App\Models\Collection;
use App\Models\Product;
use App\Services\Catalog\CollectionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function __construct(private readonly CollectionService $collections) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Collection::class);

        $search = $request->string('search')->trim()->value();

        $collections = Collection::query()
            ->withCount('products')
            ->when($search !== '', fn ($query) => $query->where('title', 'like', "%{$search}%"))
            ->orderBy('position')
            ->orderBy('title')
            ->paginate((int) config('shop.catalog.admin_per_page', 20))
            ->withQueryString();

        return Inertia::render('admin/collections/index', [
            'collections' => CollectionResource::collection($collections),
            'filters' => ['search' => $search ?: null],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', Collection::class);

        return Inertia::render('admin/collections/create', [
            'statuses' => PublishStatus::options(),
            'products' => $this->productOptions(),
        ]);
    }

    public function store(StoreCollectionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['image'] = $request->file('image');

        $collection = $this->collections->create($data);

        return to_route('admin.collections.edit', $collection)
            ->with('success', "Collection \"{$collection->title}\" was created.");
    }

    public function edit(Collection $collection): Response
    {
        $this->authorize('update', $collection);

        $collection->load('products');

        return Inertia::render('admin/collections/edit', [
            'collection' => new CollectionResource($collection),
            'statuses' => PublishStatus::options(),
            'products' => $this->productOptions(),
        ]);
    }

    public function update(UpdateCollectionRequest $request, Collection $collection): RedirectResponse
    {
        $data = $request->validated();
        $data['image'] = $request->file('image');

        $this->collections->update($collection, $data);

        return back()->with('success', 'Collection saved.');
    }

    public function destroy(Collection $collection): RedirectResponse
    {
        $this->authorize('delete', $collection);

        $title = $collection->title;

        $this->collections->delete($collection);

        return to_route('admin.collections.index')
            ->with('success', "Collection \"{$title}\" was deleted.");
    }

    /**
     * @return array<int, array{value: int, label: string}>
     */
    private function productOptions(): array
    {
        return Product::query()
            ->orderBy('title')
            ->get(['id', 'title'])
            ->map(fn (Product $product): array => [
                'value' => $product->id,
                'label' => $product->title,
            ])
            ->all();
    }
}
