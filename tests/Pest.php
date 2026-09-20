<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
 | Unit tests boot the application but get no database. Model rules and value
 | objects read configuration (currency, stock thresholds, cart limits), which
 | needs the container; nothing here should query.
 */
pest()->extend(TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function checkoutPayload(array $overrides = []): array
{
    $method = ShippingMethod::query()->first() ?? ShippingMethod::factory()->create();

    return array_merge([
        'email' => 'buyer@example.com',
        'phone' => '5550100',
        'shipping_method_id' => $method->id,
        'billing_same_as_shipping' => true,
        'save_address' => false,
        'shipping' => [
            'first_name' => 'Ada',
            'last_name' => 'Lovelace',
            'address_line1' => '1 Computing Lane',
            'city' => 'London',
            'postal_code' => 'SW1A 1AA',
            'country_code' => 'GB',
        ],
    ], $overrides);
}

/**
 * @return array{0: Cart, 1: ProductVariant}
 */
function stockedCart(int $price = 4000, int $quantity = 1, int $stock = 10): array
{
    $variant = ProductVariant::factory()->for(Product::factory())->priced($price)->withStock($stock)->create();
    $cart = Cart::factory()->create();
    $cart->items()->create([
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
        'unit_price_amount' => $variant->price(),
    ]);

    return [$cart->load(['items.variant.product', 'coupon', 'user']), $variant];
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function storeSettingsPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'store' => [
            'name' => 'Meds To Your Doors',
            'email' => 'support@medstoyourdoors.com',
            'phone' => '',
            'address' => '',
        ],
        'checkout' => [
            'tax_rate_basis_points' => 0,
            'guest_checkout_enabled' => true,
        ],
        'seo' => [
            'default_title' => 'Meds To Your Doors',
            'default_description' => 'Trusted medication delivered to your door.',
        ],
        'social' => [
            'facebook' => '',
            'instagram' => '',
            'twitter' => '',
        ],
        'ads' => [
            'enabled' => false,
            'pixel_id' => '',
            'access_token' => '',
            'test_event_code' => '',
            'advanced_matching' => true,
        ],
        'google' => [
            'enabled' => false,
            'measurement_id' => '',
        ],
        'gtm' => [
            'enabled' => false,
            'container_id' => '',
        ],
    ], $overrides);
}
