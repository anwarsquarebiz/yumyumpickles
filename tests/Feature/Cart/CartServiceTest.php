<?php

use App\Exceptions\CartException;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Cart\CartService;
use App\Support\Money;

beforeEach(function () {
    $this->carts = app(CartService::class);
});

function liveVariant(array $overrides = []): ProductVariant
{
    return ProductVariant::factory()
        ->for(Product::factory())
        ->create($overrides);
}

it('adds a variant and snapshots the unit price', function () {
    $cart = Cart::factory()->create();
    $variant = liveVariant(['price_amount' => 1250]);

    $item = $this->carts->add($cart, $variant, 2);

    expect($item->quantity)->toBe(2)
        ->and($item->unitPrice()->amount)->toBe(1250)
        ->and($cart->fresh()->items)->toHaveCount(1);
});

it('merges a second add into the existing line', function () {
    $cart = Cart::factory()->create();
    $variant = liveVariant(['inventory_quantity' => 10]);

    $this->carts->add($cart, $variant, 1);
    $this->carts->add($cart, $variant, 2);

    expect($cart->fresh()->items)->toHaveCount(1)
        ->and($cart->items->first()->quantity)->toBe(3);
});

it('refuses a draft product', function () {
    $cart = Cart::factory()->create();
    $variant = ProductVariant::factory()->for(Product::factory()->draft())->create();

    $this->carts->add($cart, $variant);
})->throws(CartException::class);

it('refuses an out of stock variant', function () {
    $cart = Cart::factory()->create();
    $variant = liveVariant(['inventory_quantity' => 0]);

    $this->carts->add($cart, $variant);
})->throws(CartException::class);

it('updates quantity and removes the line at zero', function () {
    $cart = Cart::factory()->create();
    $variant = liveVariant(['inventory_quantity' => 10]);
    $item = $this->carts->add($cart, $variant, 2);

    $this->carts->updateQuantity($cart, $item, 4);

    expect($item->fresh()->quantity)->toBe(4);

    $this->carts->updateQuantity($cart, $item, 0);

    expect($cart->fresh()->items)->toHaveCount(0);
});

it('computes subtotal from line totals', function () {
    $cart = Cart::factory()->create();
    $this->carts->add($cart, liveVariant(['price_amount' => 1000]), 2);
    $this->carts->add($cart, liveVariant(['price_amount' => 500]), 1);

    expect($this->carts->subtotal($cart->fresh('items'))->amount)->toBe(2500);
});

it('applies a fixed coupon to totals', function () {
    $cart = Cart::factory()->create();
    $this->carts->add($cart, liveVariant(['price_amount' => 3000]), 1);
    Coupon::factory()->fixed(1000)->create(['code' => 'SAVE10']);

    $this->carts->applyCoupon($cart, 'save10');

    $totals = $this->carts->totals($cart->fresh(['items', 'coupon']));

    expect($totals->discount->amount)->toBe(1000)
        ->and($totals->total()->amount)->toBe(2000)
        ->and($totals->couponCode)->toBe('SAVE10');
});

it('caps a fixed coupon at the subtotal', function () {
    $cart = Cart::factory()->create();
    $this->carts->add($cart, liveVariant(['price_amount' => 400]), 1);
    Coupon::factory()->fixed(1000)->create(['code' => 'HUGE']);

    $this->carts->applyCoupon($cart, 'HUGE');

    expect($this->carts->totals($cart->fresh(['items', 'coupon']))->total()->equals(Money::zero()))->toBeTrue();
});

it('merges a guest cart into a customer cart without exceeding stock', function () {
    $variant = liveVariant(['inventory_quantity' => 3]);
    $guest = Cart::factory()->create();
    $user = User::factory()->customer()->create();
    $owned = Cart::factory()->forUser($user)->create();

    $this->carts->add($guest, $variant, 2);
    $this->carts->add($owned, $variant, 2);

    $merged = $this->carts->merge($guest->fresh('items'), $owned->fresh('items'));

    expect($merged->items)->toHaveCount(1)
        ->and($merged->items->first()->quantity)->toBe(3)
        ->and(Cart::query()->find($guest->id))->toBeNull();
});

it('prunes unpublished products before checkout', function () {
    $cart = Cart::factory()->create();
    $live = liveVariant();
    $draft = ProductVariant::factory()->for(Product::factory()->draft())->create();

    $this->carts->add($cart, $live);
    $cart->items()->create([
        'product_variant_id' => $draft->id,
        'quantity' => 1,
        'unit_price_amount' => $draft->price(),
    ]);

    $removed = $this->carts->pruneUnavailable($cart->fresh('items.variant.product'));

    expect($removed)->toBe(1)
        ->and($cart->fresh()->items)->toHaveCount(1);
});
