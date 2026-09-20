<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CacheKeys;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates and updates products together with their options, variants and
 * collection memberships.
 *
 * Variant stock is never written directly here; it is routed through
 * InventoryService so every change leaves a movement in the ledger.
 *
 * @phpstan-type VariantPayload array{
 *     id?: int|null,
 *     title?: string|null,
 *     sku?: string|null,
 *     barcode?: string|null,
 *     price?: string|float|int,
 *     compare_at_price?: string|float|int|null,
 *     cost?: string|float|int|null,
 *     option1?: string|null,
 *     option2?: string|null,
 *     option3?: string|null,
 *     inventory_quantity?: int,
 *     track_inventory?: bool,
 *     inventory_policy?: string,
 *     weight?: float|string|null,
 *     weight_unit?: string,
 *     position?: int
 * }
 * @phpstan-type OptionPayload array{name: string, position?: int, values?: array<int, string>}
 * @phpstan-type ProductPayload array{
 *     title: string,
 *     slug?: string|null,
 *     description?: string|null,
 *     body_html?: string|null,
 *     status?: string,
 *     vendor?: string|null,
 *     product_type?: string|null,
 *     seo_title?: string|null,
 *     seo_description?: string|null,
 *     metadata?: array<string, mixed>|null,
 *     published_at?: string|null,
 *     collection_ids?: array<int, int>,
 *     options?: array<int, OptionPayload>,
 *     variants?: array<int, VariantPayload>
 * }
 */
class ProductService
{
    public function __construct(private readonly InventoryService $inventory) {}

    /**
     * @param  ProductPayload  $data
     */
    public function create(array $data): Product
    {
        $product = DB::transaction(function () use ($data): Product {
            $product = Product::query()->create($this->attributes($data));

            $this->syncOptions($product, $data['options'] ?? []);
            $this->syncVariants($product, $data['variants'] ?? []);
            $this->syncCollections($product, $data['collection_ids'] ?? null);

            return $product;
        });

        $this->flushCaches();

        return $product->fresh(['variants', 'options', 'collections']) ?? $product;
    }

    /**
     * @param  ProductPayload  $data
     */
    public function update(Product $product, array $data): Product
    {
        DB::transaction(function () use ($product, $data): void {
            $product->update($this->attributes($data, $product));

            if (array_key_exists('options', $data)) {
                $this->syncOptions($product, $data['options']);
            }

            if (array_key_exists('variants', $data)) {
                $this->syncVariants($product, $data['variants']);
            }

            $this->syncCollections($product, $data['collection_ids'] ?? null);
        });

        $this->flushCaches();

        return $product->fresh(['variants', 'options', 'collections']) ?? $product;
    }

    public function delete(Product $product): void
    {
        DB::transaction(fn () => $product->delete());

        $this->flushCaches();
    }

    public function restore(Product $product): void
    {
        $product->restore();

        $this->flushCaches();
    }

    /**
     * Apply a new catalog order. Submitted ids keep their relative slots so
     * a paginated admin page can be rearranged without moving other products.
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $ids = collect($orderedIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $products = Product::query()
            ->whereKey($ids)
            ->get(['id', 'position'])
            ->keyBy('id');

        $ids = $ids
            ->filter(fn (int $id): bool => $products->has($id))
            ->values();

        if ($ids->count() < 2) {
            return;
        }

        $slots = $ids
            ->map(fn (int $id): int => (int) $products[$id]->position)
            ->sort()
            ->values();

        DB::transaction(function () use ($ids, $slots): void {
            foreach ($ids as $index => $id) {
                Product::query()->whereKey($id)->update(['position' => $slots[$index]]);
            }
        });

        $this->flushCaches();
    }

    /**
     * Build a URL-safe, unique slug, falling back to the title when none given.
     */
    public function generateSlug(string $source, ?Product $ignore = null): string
    {
        $base = Str::slug($source) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($slug, $ignore)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  ProductPayload  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?Product $existing = null): array
    {
        $attributes = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'body_html' => $data['body_html'] ?? null,
            'vendor' => $data['vendor'] ?? null,
            'product_type' => $data['product_type'] ?? null,
            'seo_title' => $data['seo_title'] ?? null,
            'seo_description' => $data['seo_description'] ?? null,
            'published_at' => $data['published_at'] ?? null,
        ];

        if (array_key_exists('metadata', $data)) {
            $attributes['metadata'] = $data['metadata'];
        }

        if (array_key_exists('status', $data)) {
            $attributes['status'] = $data['status'];
        }

        $requestedSlug = $data['slug'] ?? null;

        /*
         | Keep an existing slug stable unless the operator explicitly changes
         | it, so published URLs do not silently break when a title is edited.
         */
        if ($requestedSlug !== null && $requestedSlug !== '') {
            $attributes['slug'] = $this->generateSlug($requestedSlug, $existing);
        } elseif ($existing === null) {
            $attributes['slug'] = $this->generateSlug($data['title']);
        }

        if ($existing === null) {
            $attributes['position'] = $this->nextPosition();
        }

        return $attributes;
    }

    /**
     * @param  array<int, OptionPayload>  $options
     */
    private function syncOptions(Product $product, array $options): void
    {
        $keptIds = [];
        $position = 1;

        foreach (array_slice($options, 0, (int) config('shop.catalog.max_options_per_product', 3)) as $option) {
            $record = $product->options()->updateOrCreate(
                ['name' => $option['name']],
                [
                    'position' => $option['position'] ?? $position,
                    'values' => $option['values'] ?? [],
                ],
            );

            $keptIds[] = $record->getKey();
            $position++;
        }

        $product->options()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * Upsert the given variants and remove any that were dropped.
     *
     * A product always keeps at least one variant, so a product with no options
     * still has something purchasable.
     *
     * @param  array<int, VariantPayload>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        if ($variants === []) {
            if ($product->variants()->exists()) {
                return;
            }

            $variants = [['title' => 'Default', 'price' => '0.00']];
        }

        /*
         | Dropped variants go first so an operator can replace a variant row and
         | reuse its SKU in the same submission without tripping the unique index.
         */
        $keptIds = collect($variants)
            ->pluck('id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->all();

        $product->variants()
            ->when($keptIds !== [], fn ($query) => $query->whereNotIn('id', $keptIds))
            ->delete();

        $position = 1;

        foreach ($variants as $payload) {
            $this->upsertVariant($product, $payload, $position);
            $position++;
        }
    }

    /**
     * @param  VariantPayload  $payload
     */
    private function upsertVariant(Product $product, array $payload, int $position): ProductVariant
    {
        $attributes = [
            'title' => $payload['title'] ?? 'Default',
            'sku' => $payload['sku'] ?? null,
            'barcode' => $payload['barcode'] ?? null,
            'price_amount' => Money::fromDecimal($payload['price'] ?? 0),
            'compare_at_price_amount' => $this->optionalMoney($payload['compare_at_price'] ?? null),
            'cost_amount' => $this->optionalMoney($payload['cost'] ?? null),
            'option1' => $payload['option1'] ?? null,
            'option2' => $payload['option2'] ?? null,
            'option3' => $payload['option3'] ?? null,
            'track_inventory' => $payload['track_inventory'] ?? true,
            'weight' => $payload['weight'] ?? null,
            'weight_unit' => $payload['weight_unit'] ?? 'kg',
            'position' => $payload['position'] ?? $position,
        ];

        if (array_key_exists('inventory_policy', $payload)) {
            $attributes['inventory_policy'] = $payload['inventory_policy'];
        }

        $existingId = $payload['id'] ?? null;

        $variant = $existingId === null
            ? $product->variants()->create([...$attributes, 'inventory_quantity' => 0])
            : tap(
                $product->variants()->whereKey($existingId)->firstOrFail(),
                fn (ProductVariant $variant) => $variant->update($attributes),
            );

        /*
         | Stock is applied as an absolute level so the change is captured as an
         | inventory movement rather than an untracked column write.
         */
        if (array_key_exists('inventory_quantity', $payload)) {
            $this->inventory->setLevel(
                variant: $variant,
                quantity: (int) $payload['inventory_quantity'],
                note: $existingId === null ? 'Initial stock' : 'Updated from product form',
            );
        }

        return $variant;
    }

    private function optionalMoney(string|float|int|null $value): ?Money
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Money::fromDecimal($value);
    }

    /**
     * @param  array<int, int>|null  $collectionIds
     */
    private function syncCollections(Product $product, ?array $collectionIds): void
    {
        if ($collectionIds === null) {
            return;
        }

        $product->collections()->sync(
            collect($collectionIds)
                ->values()
                ->mapWithKeys(fn (int $id, int $index): array => [$id => ['position' => $index]])
                ->all()
        );
    }

    private function nextPosition(): int
    {
        return ((int) Product::query()->withTrashed()->max('position')) + 1;
    }

    private function slugTaken(string $slug, ?Product $ignore): bool
    {
        return Product::query()
            ->withTrashed()
            ->where('slug', $slug)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists();
    }

    private function flushCaches(): void
    {
        CacheKeys::bump(CacheKeys::Products);
        CacheKeys::bump(CacheKeys::Collections);
    }
}
