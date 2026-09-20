<?php

use App\Models\Product;
use App\Models\ProductImage;
use App\Services\Catalog\ShopifyCatalogImporter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('imports shopify products variants collections and images', function () {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');

    $product = [
        'id' => 1,
        'title' => 'Demo Pill 100 Mg',
        'handle' => 'demo-pill-100-mg',
        'body_html' => '<p>A <strong>demo</strong> product.</p>',
        'published_at' => '2026-01-01T00:00:00Z',
        'vendor' => 'Demo Vendor',
        'product_type' => 'Tablets',
        'tags' => [],
        'options' => [
            ['name' => 'Pills', 'position' => 1, 'values' => ['30', '60']],
        ],
        'variants' => [
            [
                'id' => 11,
                'title' => '30',
                'option1' => '30',
                'option2' => null,
                'option3' => null,
                'sku' => 'DEMO-30',
                'price' => '99.00',
                'compare_at_price' => '150.00',
                'available' => true,
                'grams' => 0,
                'position' => 1,
            ],
            [
                'id' => 12,
                'title' => '60',
                'option1' => '60',
                'option2' => null,
                'option3' => null,
                'sku' => 'DEMO-60',
                'price' => '149.00',
                'compare_at_price' => null,
                'available' => false,
                'grams' => 0,
                'position' => 2,
            ],
        ],
        'images' => [
            [
                'id' => 21,
                'src' => 'https://cdn.example.test/demo.png',
                'position' => 1,
            ],
        ],
    ];

    Http::fake(function (Request $request) use ($product, $png) {
        $url = $request->url();

        if (str_contains($url, '/products.json')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return Http::response([
                'products' => ((int) ($query['page'] ?? 1)) === 1 ? [$product] : [],
            ]);
        }

        if (str_contains($url, '/collections.json')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return Http::response([
                'collections' => ((int) ($query['page'] ?? 1)) === 1 ? [
                    [
                        'id' => 100,
                        'title' => 'Erectile Dysfunction',
                        'handle' => 'erectile-dysfunction',
                        'body_html' => '<p>ED products</p>',
                        'products_count' => 1,
                    ],
                    [
                        'id' => 101,
                        'title' => 'Home page',
                        'handle' => 'frontpage',
                        'body_html' => '',
                        'products_count' => 1,
                    ],
                ] : [],
            ]);
        }

        if (str_contains($url, '/collections/erectile-dysfunction/products.json')) {
            parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

            return Http::response([
                'products' => ((int) ($query['page'] ?? 1)) === 1
                    ? [['handle' => 'demo-pill-100-mg']]
                    : [],
            ]);
        }

        if (str_contains($url, 'cdn.example.test/demo.png')) {
            return Http::response($png, 200, ['Content-Type' => 'image/png']);
        }

        return Http::response(['message' => 'not found'], 404);
    });

    $result = app(ShopifyCatalogImporter::class)->import('https://example-shop.test');

    expect($result['products_created'])->toBe(1)
        ->and($result['images_imported'])->toBe(1)
        ->and($result['collections_synced'])->toBe(1);

    $imported = Product::query()->where('slug', 'demo-pill-100-mg')->with(['variants', 'options', 'images', 'collections'])->first();

    expect($imported)->not->toBeNull()
        ->and($imported->title)->toBe('Demo Pill 100 Mg')
        ->and($imported->description)->toBe('A demo product.')
        ->and($imported->variants)->toHaveCount(2)
        ->and($imported->options)->toHaveCount(1)
        ->and($imported->images)->toHaveCount(1)
        ->and($imported->collections->pluck('slug')->all())->toContain('erectile-dysfunction')
        ->and(ProductImage::query()->count())->toBe(1);

    Storage::disk('public')->assertExists($imported->images->first()->path);
});
