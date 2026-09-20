<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductImage;
use App\Support\CacheKeys;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    /**
     * Store an upload and append it to the end of the product's gallery.
     */
    public function attach(Product $product, UploadedFile $file, ?string $alt = null, ?int $variantId = null): ProductImage
    {
        $disk = (string) config('shop.catalog.image_disk', 'public');

        $image = $product->images()->create([
            'product_variant_id' => $variantId,
            'disk' => $disk,
            'path' => $file->store('products', $disk),
            'alt' => $alt,
            'position' => (int) $product->images()->max('position') + 1,
        ]);

        CacheKeys::bump(CacheKeys::Products);

        return $image;
    }

    public function delete(ProductImage $image): void
    {
        DB::transaction(function () use ($image): void {
            Storage::disk($image->disk)->delete($image->path);
            $image->delete();
        });

        CacheKeys::bump(CacheKeys::Products);
    }

    /**
     * Apply a new gallery order. Ids not belonging to the product are ignored.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(Product $product, array $orderedIds): void
    {
        DB::transaction(function () use ($product, $orderedIds): void {
            foreach (array_values($orderedIds) as $index => $id) {
                $product->images()->whereKey($id)->update(['position' => $index + 1]);
            }
        });

        CacheKeys::bump(CacheKeys::Products);
    }

    public function updateAlt(ProductImage $image, ?string $alt): void
    {
        $image->update(['alt' => $alt]);

        CacheKeys::bump(CacheKeys::Products);
    }
}
