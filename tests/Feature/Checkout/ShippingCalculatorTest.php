<?php

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use App\Services\Checkout\ShippingCalculator;
use App\Support\Money;

it('quotes a flat rate', function () {
    $method = ShippingMethod::factory()->create(['rate_amount' => 599]);

    $amount = app(ShippingCalculator::class)->quote($method, Money::fromMinor(2000));

    expect($amount->amount)->toBe(599);
});

it('ships free once the subtotal reaches the threshold', function () {
    $method = ShippingMethod::factory()->freeOver(5000)->create();
    $calculator = app(ShippingCalculator::class);

    expect($calculator->quote($method, Money::fromMinor(4999))->amount)->toBe(799)
        ->and($calculator->quote($method, Money::fromMinor(5000))->amount)->toBe(0);
});

it('charges per started kilogram for weight based shipping', function () {
    $method = ShippingMethod::factory()->weightBased(250)->create();
    $variant = ProductVariant::factory()->for(Product::factory())->create(['weight' => 0.6]);
    $cart = Cart::factory()->create();
    $cart->items()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 2,
        'unit_price_amount' => $variant->price(),
    ]);

    $weight = app(ShippingCalculator::class)->weightKg($cart->load('items.variant'));

    expect($weight)->toEqual(1.2)
        ->and(app(ShippingCalculator::class)->quote($method, Money::fromMinor(1000), $weight)->amount)->toBe(500);
});

it('lists only active methods', function () {
    ShippingMethod::factory()->create();
    ShippingMethod::factory()->inactive()->create();

    expect(app(ShippingCalculator::class)->activeMethods())->toHaveCount(1);
});
