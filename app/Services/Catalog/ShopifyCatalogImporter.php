<?php

namespace App\Services\Catalog;

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Enums\PublishStatus;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Imports a public Shopify storefront catalog (products + images + collections)
 * into the local catalog using ProductService / ProductImageService.
 */
class ShopifyCatalogImporter
{
    /**
     * Collection handles that are Shopify system surfaces, not real categories.
     *
     * @var list<string>
     */
    private const SkippedCollectionHandles = [
        'frontpage',
        'all',
    ];

    public function __construct(
        private readonly ProductService $products,
        private readonly ProductImageService $images,
    ) {}

    /**
     * @return array{products_created: int, products_updated: int, images_imported: int, collections_synced: int}
     */
    public function import(string $storeUrl, bool $refreshImages = false, bool $verifySsl = true): array
    {
        $base = rtrim($storeUrl, '/');

        $remoteProducts = $this->fetchAllProducts($base, $verifySsl);
        [$collectionMap, $collectionsSynced] = $this->syncCollections($base, $remoteProducts, $verifySsl);

        return $this->persistCatalog($remoteProducts, $collectionMap, $collectionsSynced, $refreshImages, $verifySsl);
    }

    /**
     * Import from a previously downloaded dump (catalog.json + local image files).
     *
     * @return array{products_created: int, products_updated: int, images_imported: int, collections_synced: int}
     */
    public function importFromDump(string $dumpPath, bool $refreshImages = false): array
    {
        $absolute = base_path($dumpPath);
        if (! is_file($absolute)) {
            $absolute = $dumpPath;
        }

        if (! is_file($absolute)) {
            throw new \InvalidArgumentException("Catalog dump not found: {$dumpPath}");
        }

        /** @var array{products?: list<array<string, mixed>>, collections?: list<array<string, mixed>>, membership?: array<string, list<string>>, images?: array<string, string>} $dump */
        $dump = json_decode((string) file_get_contents($absolute), true, flags: JSON_THROW_ON_ERROR);

        $remoteProducts = $dump['products'] ?? [];
        $membershipByHandle = $dump['membership'] ?? [];
        $localImages = $dump['images'] ?? [];

        [$collectionMap, $collectionsSynced] = $this->syncCollectionsFromDump(
            $dump['collections'] ?? [],
            $membershipByHandle,
            $remoteProducts,
        );

        return $this->persistCatalog(
            remoteProducts: $remoteProducts,
            collectionMap: $collectionMap,
            collectionsSynced: $collectionsSynced,
            refreshImages: $refreshImages,
            verifySsl: true,
            localImageMap: $localImages,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $remoteProducts
     * @param  array<string, list<int>>  $collectionMap
     * @param  array<string, string>  $localImageMap
     * @return array{products_created: int, products_updated: int, images_imported: int, collections_synced: int}
     */
    private function persistCatalog(
        array $remoteProducts,
        array $collectionMap,
        int $collectionsSynced,
        bool $refreshImages,
        bool $verifySsl,
        array $localImageMap = [],
    ): array {
        $created = 0;
        $updated = 0;
        $imagesImported = 0;

        foreach ($remoteProducts as $remote) {
            $slug = (string) ($remote['handle'] ?? Str::slug((string) $remote['title']));
            $existing = Product::query()->with(['variants'])->withTrashed()->where('slug', $slug)->first();

            if ($existing?->trashed()) {
                $existing->restore();
            }

            $payload = $this->productPayload($remote, $collectionMap[$slug] ?? [], $existing);

            if ($existing === null) {
                $product = $this->products->create($payload);
                $created++;
            } else {
                $product = $this->products->update($existing, $payload);
                $updated++;
            }

            $imagesImported += $this->importImages(
                product: $product,
                remoteImages: $remote['images'] ?? [],
                refreshImages: $refreshImages,
                verifySsl: $verifySsl,
                localImageMap: $localImageMap,
            );
        }

        return [
            'products_created' => $created,
            'products_updated' => $updated,
            'images_imported' => $imagesImported,
            'collections_synced' => $collectionsSynced,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchAllProducts(string $base, bool $verifySsl): array
    {
        $products = [];
        $page = 1;

        do {
            $response = $this->http($verifySsl)
                ->get("{$base}/products.json", [
                    'limit' => 250,
                    'page' => $page,
                ])
                ->throw();

            /** @var list<array<string, mixed>> $batch */
            $batch = $response->json('products') ?? [];
            $products = [...$products, ...$batch];
            $page++;
        } while ($batch !== []);

        return $products;
    }

    /**
     * Sync Shopify collections and return [product handle => collection ids, synced count].
     *
     * @param  list<array<string, mixed>>  $remoteProducts
     * @return array{0: array<string, list<int>>, 1: int}
     */
    private function syncCollections(string $base, array $remoteProducts, bool $verifySsl): array
    {
        $membership = [];

        foreach ($remoteProducts as $product) {
            $handle = (string) ($product['handle'] ?? '');
            if ($handle !== '') {
                $membership[$handle] = [];
            }
        }

        $synced = 0;
        $page = 1;

        do {
            $response = $this->http($verifySsl)
                ->get("{$base}/collections.json", [
                    'limit' => 250,
                    'page' => $page,
                ])
                ->throw();

            /** @var list<array<string, mixed>> $collections */
            $collections = $response->json('collections') ?? [];

            foreach ($collections as $remoteCollection) {
                $handle = (string) ($remoteCollection['handle'] ?? '');

                if ($handle === '' || in_array($handle, self::SkippedCollectionHandles, true)) {
                    continue;
                }

                if ((int) ($remoteCollection['products_count'] ?? 0) === 0) {
                    continue;
                }

                $collection = Collection::query()->updateOrCreate(
                    ['slug' => $handle],
                    [
                        'title' => (string) ($remoteCollection['title'] ?? $handle),
                        'description' => $this->plainText($remoteCollection['body_html'] ?? null),
                        'status' => PublishStatus::Published,
                        'published_at' => now()->subMinute(),
                        'position' => (int) ($remoteCollection['id'] ?? 0) % 100000,
                    ],
                );

                $synced++;

                foreach ($this->fetchCollectionProductHandles($base, $handle, $verifySsl) as $productHandle) {
                    if (! array_key_exists($productHandle, $membership)) {
                        continue;
                    }

                    $membership[$productHandle][] = $collection->id;
                }
            }

            $page++;
        } while ($collections !== []);

        return [$membership, $synced];
    }

    /**
     * @param  list<array<string, mixed>>  $collections
     * @param  array<string, list<string>>  $membershipByHandle
     * @param  list<array<string, mixed>>  $remoteProducts
     * @return array{0: array<string, list<int>>, 1: int}
     */
    private function syncCollectionsFromDump(array $collections, array $membershipByHandle, array $remoteProducts): array
    {
        $membership = [];

        foreach ($remoteProducts as $product) {
            $handle = (string) ($product['handle'] ?? '');
            if ($handle !== '') {
                $membership[$handle] = [];
            }
        }

        $synced = 0;

        foreach ($collections as $remoteCollection) {
            $handle = (string) ($remoteCollection['handle'] ?? '');

            if ($handle === '' || in_array($handle, self::SkippedCollectionHandles, true)) {
                continue;
            }

            $productHandles = $membershipByHandle[$handle] ?? [];

            if ($productHandles === [] && (int) ($remoteCollection['products_count'] ?? 0) === 0) {
                continue;
            }

            if ($productHandles === []) {
                continue;
            }

            $collection = Collection::query()->updateOrCreate(
                ['slug' => $handle],
                [
                    'title' => (string) ($remoteCollection['title'] ?? $handle),
                    'description' => $this->plainText($remoteCollection['body_html'] ?? null),
                    'status' => PublishStatus::Published,
                    'published_at' => now()->subMinute(),
                    'position' => (int) ($remoteCollection['id'] ?? 0) % 100000,
                ],
            );

            $synced++;

            foreach ($productHandles as $productHandle) {
                if (! array_key_exists($productHandle, $membership)) {
                    continue;
                }

                $membership[$productHandle][] = $collection->id;
            }
        }

        return [$membership, $synced];
    }

    /**
     * @return list<string>
     */
    private function fetchCollectionProductHandles(string $base, string $collectionHandle, bool $verifySsl): array
    {
        $handles = [];
        $page = 1;

        do {
            $response = $this->http($verifySsl)
                ->get("{$base}/collections/{$collectionHandle}/products.json", [
                    'limit' => 250,
                    'page' => $page,
                ]);

            if (! $response->successful()) {
                break;
            }

            /** @var list<array<string, mixed>> $products */
            $products = $response->json('products') ?? [];

            foreach ($products as $product) {
                $handle = (string) ($product['handle'] ?? '');
                if ($handle !== '') {
                    $handles[] = $handle;
                }
            }

            $page++;
        } while ($products !== []);

        return $handles;
    }

    /**
     * @param  array<string, mixed>  $remote
     * @param  list<int>  $collectionIds
     * @return array<string, mixed>
     */
    private function productPayload(array $remote, array $collectionIds, ?Product $existing = null): array
    {
        $title = (string) $remote['title'];
        $bodyHtml = is_string($remote['body_html'] ?? null) ? $remote['body_html'] : null;
        $publishedAt = $remote['published_at'] ?? null;

        return [
            'title' => $title,
            'slug' => (string) ($remote['handle'] ?? Str::slug($title)),
            'description' => $this->plainText($bodyHtml),
            'body_html' => $bodyHtml,
            'status' => ProductStatus::Active->value,
            'vendor' => $this->nullableString($remote['vendor'] ?? null) ?? 'World Online Meds',
            'product_type' => $this->nullableString($remote['product_type'] ?? null),
            'seo_title' => $title,
            'seo_description' => Str::limit($this->plainText($bodyHtml) ?? '', 255, ''),
            'published_at' => is_string($publishedAt) && $publishedAt !== ''
                ? $publishedAt
                : now()->subMinute()->toDateTimeString(),
            'collection_ids' => array_values(array_unique($collectionIds)),
            'options' => $this->mapOptions($remote['options'] ?? []),
            'variants' => $this->mapVariants($remote, $existing),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $options
     * @return list<array{name: string, position: int, values: list<string>}>
     */
    private function mapOptions(array $options): array
    {
        $mapped = [];

        foreach ($options as $index => $option) {
            $name = (string) ($option['name'] ?? '');

            if ($name === '' || strcasecmp($name, 'Title') === 0) {
                continue;
            }

            /** @var list<string> $values */
            $values = array_values(array_filter(
                array_map(fn ($value): string => (string) $value, $option['values'] ?? []),
                fn (string $value): bool => $value !== '' && strcasecmp($value, 'Default Title') !== 0,
            ));

            if ($values === []) {
                continue;
            }

            $mapped[] = [
                'name' => $name,
                'position' => (int) ($option['position'] ?? ($index + 1)),
                'values' => $values,
            ];
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $remote
     * @return list<array<string, mixed>>
     */
    private function mapVariants(array $remote, ?Product $existing = null): array
    {
        $handle = (string) ($remote['handle'] ?? 'product');
        $existingBySku = $existing?->variants->keyBy(fn ($variant) => (string) $variant->sku) ?? collect();
        $variants = [];

        foreach ($remote['variants'] ?? [] as $index => $variant) {
            $option1 = $this->nullableString($variant['option1'] ?? null);
            if ($option1 !== null && strcasecmp($option1, 'Default Title') === 0) {
                $option1 = null;
            }

            $rawSku = $this->nullableString($variant['sku'] ?? null);
            $skuSeed = $rawSku
                ?? strtoupper(Str::slug($handle.'-'.($option1 ?? 'default'), '-'));
            $sku = Str::limit(
                strtoupper(Str::slug($skuSeed.'-'.($option1 ?? 'default').'-'.($index + 1), '-')),
                64,
                '',
            );

            $available = (bool) ($variant['available'] ?? false);
            $grams = (int) ($variant['grams'] ?? 0);

            $mapped = [
                'title' => $this->nullableString($variant['title'] ?? null) ?? ($option1 ?? 'Default'),
                'sku' => $sku,
                'price' => (string) ($variant['price'] ?? '0.00'),
                'compare_at_price' => $this->nullableString($variant['compare_at_price'] ?? null),
                'option1' => $option1,
                'option2' => $this->nullableString($variant['option2'] ?? null),
                'option3' => $this->nullableString($variant['option3'] ?? null),
                'inventory_quantity' => $available ? 100 : 0,
                'track_inventory' => true,
                'inventory_policy' => InventoryPolicy::Continue->value,
                'weight' => $grams > 0 ? round($grams / 1000, 3) : null,
                'weight_unit' => 'kg',
                'position' => (int) ($variant['position'] ?? ($index + 1)),
            ];

            $existingVariant = $existingBySku->get($sku);
            if ($existingVariant !== null) {
                $mapped['id'] = $existingVariant->id;
            }

            $variants[] = $mapped;
        }

        return $variants;
    }

    /**
     * @param  list<array<string, mixed>>  $remoteImages
     * @param  array<string, string>  $localImageMap
     */
    private function importImages(
        Product $product,
        array $remoteImages,
        bool $refreshImages,
        bool $verifySsl,
        array $localImageMap = [],
    ): int {
        $product->loadMissing('images');

        if ($product->images->isNotEmpty() && ! $refreshImages) {
            return 0;
        }

        if ($refreshImages) {
            foreach ($product->images as $image) {
                $this->images->delete($image);
            }
        }

        $imported = 0;

        foreach ($remoteImages as $remoteImage) {
            $src = $this->nullableString($remoteImage['src'] ?? null);

            if ($src === null) {
                continue;
            }

            $upload = isset($localImageMap[$src])
                ? $this->uploadFromLocalPath($localImageMap[$src])
                : $this->downloadImage($src, $verifySsl);

            if ($upload === null) {
                continue;
            }

            try {
                $this->images->attach(
                    product: $product,
                    file: $upload,
                    alt: $product->title,
                );
                $imported++;
            } finally {
                if (is_string($upload->getRealPath()) && file_exists($upload->getRealPath())) {
                    @unlink($upload->getRealPath());
                }
            }
        }

        return $imported;
    }

    private function uploadFromLocalPath(string $path): ?UploadedFile
    {
        $absolute = base_path($path);
        if (! is_file($absolute)) {
            $absolute = $path;
        }

        if (! is_file($absolute)) {
            return null;
        }

        $basename = basename($absolute);
        $extension = pathinfo($basename, PATHINFO_EXTENSION) ?: 'jpg';
        $tempPath = tempnam(sys_get_temp_dir(), 'shopify_');

        if ($tempPath === false) {
            return null;
        }

        $finalPath = $tempPath.'.'.$extension;
        rename($tempPath, $finalPath);
        copy($absolute, $finalPath);

        $mime = mime_content_type($absolute) ?: 'image/jpeg';

        return new UploadedFile($finalPath, $basename, $mime, null, true);
    }

    private function downloadImage(string $url, bool $verifySsl): ?UploadedFile
    {
        try {
            $response = $this->http($verifySsl)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        if (! $response->successful() || $response->body() === '') {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);
        $basename = is_string($path) ? basename($path) : 'image.jpg';
        $basename = strtok($basename, '?') ?: 'image.jpg';

        $extension = pathinfo($basename, PATHINFO_EXTENSION) ?: 'jpg';
        $tempPath = tempnam(sys_get_temp_dir(), 'shopify_');

        if ($tempPath === false) {
            return null;
        }

        $finalPath = $tempPath.'.'.$extension;
        rename($tempPath, $finalPath);
        file_put_contents($finalPath, $response->body());

        return new UploadedFile(
            $finalPath,
            $basename,
            $response->header('Content-Type') ?: 'image/jpeg',
            null,
            true,
        );
    }

    private function http(bool $verifySsl): PendingRequest
    {
        $request = Http::timeout(60)->acceptJson();

        return $verifySsl ? $request : $request->withoutVerifying();
    }

    private function plainText(mixed $html): ?string
    {
        if (! is_string($html) || trim($html) === '') {
            return null;
        }

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;
        $text = trim($text);

        return $text === '' ? null : $text;
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
