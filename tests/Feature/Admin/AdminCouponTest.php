<?php

use App\Models\Coupon;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/**
 * @return array<string, mixed>
 */
function adminCouponPayload(array $overrides = []): array
{
    return array_merge([
        'code' => 'WELCOME10',
        'description' => 'Ten dollars off',
        'type' => 'fixed_amount',
        'value' => '10.00',
        'minimum_subtotal' => '',
        'usage_limit' => '',
        'usage_limit_per_customer' => '',
        'starts_at' => '',
        'expires_at' => '',
        'is_active' => true,
    ], $overrides);
}

it('lists coupons for staff', function () {
    Coupon::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->get('/admin/coupons')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/coupons/index')
            ->has('coupons.data', 2)
        );
});

it('creates a fixed coupon', function () {
    $this->actingAs($this->admin)
        ->post('/admin/coupons', adminCouponPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $coupon = Coupon::query()->firstOrFail();

    expect($coupon->code)->toBe('WELCOME10')
        ->and($coupon->value)->toBe(1000);
});

it('creates a percentage coupon', function () {
    $this->actingAs($this->admin)
        ->post('/admin/coupons', adminCouponPayload([
            'code' => 'TENPCT',
            'type' => 'percentage',
            'value' => '10',
        ]));

    expect(Coupon::query()->firstOrFail()->value)->toBe(1000);
});

it('rejects a percentage over 100', function () {
    $this->actingAs($this->admin)
        ->post('/admin/coupons', adminCouponPayload([
            'type' => 'percentage',
            'value' => '150',
        ]))
        ->assertSessionHasErrors('value');
});

it('rejects a duplicate code', function () {
    Coupon::factory()->create(['code' => 'TAKEN']);

    $this->actingAs($this->admin)
        ->post('/admin/coupons', adminCouponPayload(['code' => 'TAKEN']))
        ->assertSessionHasErrors('code');
});

it('updates a coupon', function () {
    $coupon = Coupon::factory()->create(['code' => 'OLD']);

    $this->actingAs($this->admin)
        ->from("/admin/coupons/{$coupon->id}/edit")
        ->put("/admin/coupons/{$coupon->id}", adminCouponPayload(['code' => 'NEWCODE', 'value' => '7.50']))
        ->assertRedirect("/admin/coupons/{$coupon->id}/edit");

    expect($coupon->fresh()->code)->toBe('NEWCODE')
        ->and($coupon->fresh()->value)->toBe(750);
});

it('deletes a coupon', function () {
    $coupon = Coupon::factory()->create();

    $this->actingAs($this->admin)
        ->delete("/admin/coupons/{$coupon->id}")
        ->assertRedirect('/admin/coupons');

    expect(Coupon::query()->count())->toBe(0);
});

it('forbids a customer from managing coupons', function () {
    $coupon = Coupon::factory()->create();
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->get('/admin/coupons')->assertForbidden();
    $this->actingAs($customer)->post('/admin/coupons', adminCouponPayload())->assertForbidden();
    $this->actingAs($customer)->delete("/admin/coupons/{$coupon->id}")->assertForbidden();
});
