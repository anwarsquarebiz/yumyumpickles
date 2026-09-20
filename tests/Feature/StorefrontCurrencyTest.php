<?php

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Currency\ExchangeRateService;
use Illuminate\Support\Facades\Http;

it('defaults the storefront to USD when the visitor IP cannot be geolocated', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('currency.current', 'USD')
            ->where('currency.base', 'USD')
            ->has('currency.options')
        );
});

it('picks a default currency from the CDN country header', function () {
    config(['shop.currency.detect_from_ip' => true]);

    $this->withHeaders(['CF-IPCountry' => 'GB'])
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('currency.current', 'GBP'));
});

it('looks up a public IP when no country header is present', function () {
    config(['shop.currency.detect_from_ip' => true]);

    Http::preventStrayRequests();
    Http::fake([
        'ipwho.is/*' => Http::response([
            'success' => true,
            'country_code' => 'IN',
        ]),
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('currency.current', 'INR'));
});

it('does not override an explicit currency with IP detection', function () {
    config(['shop.currency.detect_from_ip' => true]);

    $this->withSession(['display_currency' => 'EUR'])
        ->withHeaders(['CF-IPCountry' => 'IN'])
        ->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('currency.current', 'EUR'));
});

it('lets a visitor switch currency from the header', function () {
    $this->from('/')
        ->post('/currency', ['currency' => 'EUR'])
        ->assertRedirect('/')
        ->assertSessionHas('display_currency', 'EUR');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('currency.current', 'EUR'));
});

it('rejects an unsupported currency', function () {
    $this->from('/')
        ->post('/currency', ['currency' => 'XXX'])
        ->assertRedirect('/')
        ->assertSessionHasErrors('currency');
});

it('converts catalog prices from the USD base into the display currency', function () {
    app(ExchangeRateService::class)->useRates(['USD' => 1, 'EUR' => 0.5]);

    $product = Product::factory()->create(['slug' => 'vitamin-c']);
    ProductVariant::factory()->for($product)->priced(1250)->create();

    $this->withSession(['display_currency' => 'EUR'])
        ->get('/products/vitamin-c')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.data.variants.0.price.currency', 'EUR')
            ->where('product.data.variants.0.price.amount', 625)
            ->where('product.data.variants.0.price.formatted', '€6.25')
        );
});

it('keeps admin catalog prices in USD even when the session currency is not', function () {
    $admin = User::factory()->admin()->create();
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->priced(1250)->create();

    $this->actingAs($admin)
        ->withSession(['display_currency' => 'EUR'])
        ->get('/admin/products/'.$product->id.'/edit')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('product.data.variants.0.price.currency', 'USD')
            ->where('product.data.variants.0.price.amount', 1250)
            ->where('product.data.variants.0.price.formatted', '$12.50')
        );
});

it('converts a display-currency price filter back to USD before querying', function () {
    app(ExchangeRateService::class)->useRates(['USD' => 1, 'EUR' => 0.5]);

    $cheap = Product::factory()->create(['title' => 'Cheap']);
    ProductVariant::factory()->for($cheap)->priced(1000)->create();

    $expensive = Product::factory()->create(['title' => 'Expensive']);
    ProductVariant::factory()->for($expensive)->priced(5000)->create();

    $this->withSession(['display_currency' => 'EUR'])
        ->get('/products?min_price=20')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('products.data', 1)
            ->where('products.data.0.title', 'Expensive')
            ->where('filters.min_price', '20')
        );
});
