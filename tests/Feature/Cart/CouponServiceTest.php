<?php

use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use App\Services\Cart\CouponService;
use App\Support\Money;

beforeEach(function () {
    $this->coupons = app(CouponService::class);
});

it('looks up a code case-insensitively', function () {
    Coupon::factory()->create(['code' => 'WELCOME']);

    expect($this->coupons->findByCode('welcome')->code)->toBe('WELCOME');
});

it('rejects an unknown code', function () {
    $this->coupons->findByCode('MISSING');
})->throws(CouponException::class);

it('rejects an inactive, expired, scheduled or exhausted code', function (string $state) {
    $coupon = Coupon::factory()->$state()->create();

    $this->coupons->assertUsable($coupon, Money::fromMinor(5000));
})->with([
    'inactive' => ['inactive'],
    'expired' => ['expired'],
    'scheduled' => ['scheduled'],
    'exhausted' => ['exhausted'],
])->throws(CouponException::class);

it('rejects a cart below the minimum subtotal', function () {
    $coupon = Coupon::factory()->minimumSubtotal(5000)->create();

    $this->coupons->assertUsable($coupon, Money::fromMinor(1000));
})->throws(CouponException::class);

it('computes a percentage discount in basis points', function () {
    $coupon = Coupon::factory()->percentage(10)->create();

    expect($this->coupons->discountFor($coupon, Money::fromMinor(2000))->amount)->toBe(200);
});

it('creates a coupon converting a decimal amount to minor units', function () {
    $coupon = $this->coupons->create([
        'code' => 'five-off',
        'type' => 'fixed_amount',
        'value' => '5.00',
    ]);

    expect($coupon->code)->toBe('FIVE-OFF')
        ->and($coupon->value)->toBe(500);
});

it('creates a percentage coupon converting percent to basis points', function () {
    $coupon = $this->coupons->create([
        'code' => 'TEN',
        'type' => 'percentage',
        'value' => 10,
    ]);

    expect($coupon->value)->toBe(1000)
        ->and($coupon->displayValue())->toBe('10%');
});

it('records a redemption and increments the counter atomically', function () {
    $coupon = Coupon::factory()->create();
    $user = User::factory()->customer()->create();

    $order = Order::factory()->for($user)->create();

    $this->coupons->redeem($coupon, $order->id, Money::fromMinor(500), $user);

    expect($coupon->fresh()->used_count)->toBe(1)
        ->and($this->coupons->redemptionCountFor($coupon, $user))->toBe(1);
});
