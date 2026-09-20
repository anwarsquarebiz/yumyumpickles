<?php

namespace App\Services\Catalog;

use App\Models\Collection;
use App\Support\CacheKeys;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @phpstan-type CollectionPayload array{
 *     title: string,
 *     slug?: string|null,
 *     description?: string|null,
 *     status?: string,
 *     seo_title?: string|null,
 *     seo_description?: string|null,
 *     position?: int,
 *     published_at?: string|null,
 *     product_ids?: array<int, int>,
 *     image?: UploadedFile|null,
 *     remove_image?: bool
 * }
 */
class CollectionService
{
    /**
     * @param  CollectionPayload  $data
     */
    public function create(array $data): Collection
    {
        $collection = DB::transaction(function () use ($data): Collection {
            $collection = Collection::query()->create($this->attributes($data));

            $this->storeImage($collection, $data['image'] ?? null);
            $this->syncProducts($collection, $data['product_ids'] ?? null);

            return $collection;
        });

        $this->flushCaches();

        return $collection;
    }

    /**
     * @param  CollectionPayload  $data
     */
    public function update(Collection $collection, array $data): Collection
    {
        DB::transaction(function () use ($collection, $data): void {
            $collection->update($this->attributes($data, $collection));

            if (($data['remove_image'] ?? false) === true) {
                $this->deleteImage($collection);
            }

            $this->storeImage($collection, $data['image'] ?? null);
            $this->syncProducts($collection, $data['product_ids'] ?? null);
        });

        $this->flushCaches();

        return $collection->refresh();
    }

    public function delete(Collection $collection): void
    {
        DB::transaction(function () use ($collection): void {
            $this->deleteImage($collection);
            $collection->delete();
        });

        $this->flushCaches();
    }

    public function generateSlug(string $source, ?Collection $ignore = null): string
    {
        $base = Str::slug($source) ?: 'collection';
        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($slug, $ignore)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  CollectionPayload  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?Collection $existing = null): array
    {
        $attributes = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'position' => $data['position'] ?? 0,
            'published_at' => $data['published_at'] ?? null,
        ];

        if (array_key_exists('status', $data)) {
            $attributes['status'] = $data['status'];
        }

        $requestedSlug = $data['slug'] ?? null;

        if ($requestedSlug !== null && $requestedSlug !== '') {
            $attributes['slug'] = $this->generateSlug($requestedSlug, $existing);
        } elseif ($existing === null) {
            $attributes['slug'] = $this->generateSlug($data['title']);
        }

        return $attributes;
    }

    /**
     * @param  array<int, int>|null  $productIds
     */
    private function syncProducts(Collection $collection, ?array $productIds): void
    {
        if ($productIds === null) {
            return;
        }

        $collection->products()->sync(
            collect($productIds)
                ->values()
                ->mapWithKeys(fn (int $id, int $index): array => [$id => ['position' => $index]])
                ->all()
        );
    }

    private function storeImage(Collection $collection, ?UploadedFile $image): void
    {
        if ($image === null) {
            return;
        }

        $this->deleteImage($collection);

        $disk = (string) config('shop.catalog.image_disk', 'public');

        $collection->forceFill([
            'image_disk' => $disk,
            'image_path' => $image->store('collections', $disk),
        ])->save();
    }

    private function deleteImage(Collection $collection): void
    {
        if ($collection->image_path === null || $collection->image_disk === null) {
            return;
        }

        Storage::disk($collection->image_disk)->delete($collection->image_path);

        $collection->forceFill(['image_disk' => null, 'image_path' => null])->save();
    }

    private function slugTaken(string $slug, ?Collection $ignore): bool
    {
        return Collection::query()
            ->where('slug', $slug)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();
    }

    private function flushCaches(): void
    {
        CacheKeys::bump(CacheKeys::Collections);
        CacheKeys::bump(CacheKeys::Products);
        CacheKeys::bump(CacheKeys::Navigation);
    }
}
