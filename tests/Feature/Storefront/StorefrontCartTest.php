<?php

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;

function addLiveVariantToCart(?User $user = null, array $overrides = [], int $quantity = 1): ProductVariant
{
    $variant = ProductVariant::factory()->for(Product::factory())->create($overrides);

    $request = $user ? test()->actingAs($user) : test();

    $request->post('/cart/items', [
        'product_variant_id' => $variant->id,
        'quantity' => $quantity,
    ])->assertRedirect();

    return $variant;
}

it('renders an empty cart', function () {
    $this->get('/cart')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/cart')
            ->where('cart', null)
        );
});

it('adds an item to a customer cart and shows it', function () {
    $user = User::factory()->customer()->create();
    addLiveVariantToCart($user, ['price_amount' => 1500], 2);

    $this->actingAs($user)
        ->get('/cart')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/cart')
            ->has('cart.data.items', 1)
            ->where('cart.data.items.0.quantity', 2)
            ->where('cart.data.totals.subtotal.amount', 3000)
        );

    expect(Cart::query()->where('user_id', $user->id)->count())->toBe(1);
});

it('flashes open_cart and shares drawer line items after adding a product', function () {
    $user = User::factory()->customer()->create();
    $variant = ProductVariant::factory()->for(Product::factory())->create(['price_amount' => 1500]);

    $this->actingAs($user)
        ->from('/products/'.$variant->product->slug)
        ->post('/cart/items', [
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ])
        ->assertRedirect('/products/'.$variant->product->slug)
        ->assertSessionHas('open_cart', true);

    $this->actingAs($user)
        ->get('/products/'.$variant->product->slug)
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('cart.item_count', 1)
            ->has('cart.items', 1)
            ->where('cart.items.0.product.title', $variant->product->title)
            ->where('cart.subtotal.amount', 1500)
        );
});

it('updates and removes a line', function () {
    $user = User::factory()->customer()->create();
    addLiveVariantToCart($user, ['inventory_quantity' => 10], 1);
    $item = Cart::query()->where('user_id', $user->id)->first()->items()->first();

    $this->actingAs($user)->patch("/cart/items/{$item->id}", ['quantity' => 3])->assertRedirect();
    expect($item->fresh()->quantity)->toBe(3);

    $this->actingAs($user)->delete("/cart/items/{$item->id}")->assertRedirect();
    expect(Cart::query()->where('user_id', $user->id)->first()->items()->count())->toBe(0);
});

it('applies and removes a coupon', function () {
    $user = User::factory()->customer()->create();
    addLiveVariantToCart($user, ['price_amount' => 4000], 1);
    Coupon::factory()->fixed(1000)->create(['code' => 'SAVE10']);

    $this->actingAs($user)
        ->post('/cart/coupon', ['code' => 'save10'])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->actingAs($user)->get('/cart')->assertInertia(fn ($page) => $page
        ->where('cart.data.totals.discount.amount', 1000)
        ->where('cart.data.coupon.code', 'SAVE10')
    );

    $this->actingAs($user)->delete('/cart/coupon')->assertRedirect();

    $this->actingAs($user)->get('/cart')->assertInertia(fn ($page) => $page
        ->where('cart.data.totals.discount.amount', 0)
        ->where('cart.data.coupon', null)
    );
});

it('rejects an invalid coupon with a field error', function () {
    $user = User::factory()->customer()->create();
    addLiveVariantToCart($user, ['price_amount' => 4000], 1);

    $this->actingAs($user)
        ->from('/cart')
        ->post('/cart/coupon', ['code' => 'NOPE'])
        ->assertRedirect('/cart')
        ->assertSessionHasErrors('code');
});

it('forbids mutating another visitor\'s cart line', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create();
    $foreign = Cart::factory()->create();
    $item = $foreign->items()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => $variant->price(),
    ]);

    $this->actingAs(User::factory()->customer()->create())
        ->patch("/cart/items/{$item->id}", ['quantity' => 2])
        ->assertNotFound();
});

it('merges a guest cart when the customer logs in', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->create(['price_amount' => 1000]);
    $guestCart = Cart::factory()->create();
    $guestCart->items()->create([
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price_amount' => $variant->price(),
    ]);

    $user = User::factory()->customer()->create();

    $this->withCookie(config('shop.cart.cookie'), $guestCart->token)
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
        ->assertRedirect();

    expect(Cart::query()->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(Cart::query()->where('user_id', $user->id)->first()->items()->where('product_variant_id', $variant->id)->exists())->toBeTrue();
});
