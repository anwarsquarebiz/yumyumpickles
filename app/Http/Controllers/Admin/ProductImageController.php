<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Product\StoreProductImageRequest;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\ProductImageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductImageController extends Controller
{
    public function __construct(private readonly ProductImageService $images) {}

    public function store(StoreProductImageRequest $request, Product $product): RedirectResponse
    {
        $this->images->attach(
            product: $product,
            file: $request->file('image'),
            alt: $request->input('alt'),
            variantId: $request->integer('product_variant_id') ?: null,
        );

        return back()->with('success', 'Image uploaded.');
    }

    public function destroy(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_if($image->product_id !== $product->id, 404);

        $this->images->delete($image);

        return back()->with('success', 'Image removed.');
    }

    public function reorder(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $this->images->reorder($product, $validated['ids']);

        return back()->with('success', 'Image order updated.');
    }
}
