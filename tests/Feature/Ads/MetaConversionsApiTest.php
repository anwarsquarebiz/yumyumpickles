<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Services\Ads\MetaAdsSettings;
use App\Services\Ads\MetaUserData;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

function enableMetaAds(array $overrides = []): void
{
    app(MetaAdsSettings::class)->update(array_merge([
        'enabled' => true,
        'pixel_id' => '123456789012345',
        'access_token' => 'meta-capi-token',
        'test_event_code' => 'TEST12345',
        'advanced_matching' => true,
    ], $overrides));
}

it('snapshots pixel cookies and an event id when the order is placed', function () {
    [$cart] = stockedCart();
    $method = ShippingMethod::factory()->create();

    $this->withUnencryptedCookie('_fbp', 'fb.1.1710000000.AbC')
        ->withUnencryptedCookie('_fbc', 'fb.1.1710000000.AbC.xyz')
        ->withCookie('cart_token', $cart->token)
        ->post('/checkout', checkoutPayload(['shipping_method_id' => $method->id]))
        ->assertRedirect();

    $order = Order::query()->first();

    expect($order)->not->toBeNull()
        ->and($order->ads_attribution['event_id'] ?? null)->toBeString()
        ->and($order->ads_attribution['fbp'])->toBe('fb.1.1710000000.AbC')
        ->and($order->ads_attribution['fbc'])->toBe('fb.1.1710000000.AbC.xyz')
        ->and($order->ads_attribution['client_ip'])->not->toBeEmpty();
});

it('sends a hashed purchase event to Meta when payment is captured', function () {
    Mail::fake();
    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(['events_received' => 1]),
    ]);

    enableMetaAds();

    [$cart] = stockedCart();
    ShippingMethod::factory()->create();

    $order = app(CheckoutService::class)->place($cart, checkoutPayload());
    $payment = $order->payments()->firstOrFail();
    $eventId = $order->ads_attribution['event_id'];

    $this->postJson('/webhooks/payments/fake', [
        'id' => $payment->gateway_reference,
        'status' => 'paid',
    ], ['X-Api-Key' => 'testing-webhook-key'])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid);

    Http::assertSent(function (Request $request) use ($order, $eventId): bool {
        $body = $request->data();
        $event = $body['data'][0] ?? [];
        $custom = $event['custom_data'] ?? [];

        return $request->url() === 'https://graph.facebook.com/v21.0/123456789012345/events'
            && ($event['event_name'] ?? null) === 'Purchase'
            && ($event['event_id'] ?? null) === $eventId
            && ($event['action_source'] ?? null) === 'website'
            && ($custom['currency'] ?? null) === $order->currency
            && ($custom['value'] ?? null) === (float) $order->grandTotal()->toDecimal()
            && ($custom['order_id'] ?? null) === $order->order_number
            && ($event['user_data']['em'][0] ?? null) === MetaUserData::hashEmail('buyer@example.com')
            && ($body['access_token'] ?? null) === 'meta-capi-token'
            && ($body['test_event_code'] ?? null) === 'TEST12345';
    });
});

it('does not call Meta when the pixel is disabled', function () {
    Mail::fake();
    Http::preventStrayRequests();
    Http::fake();

    [$cart] = stockedCart();
    ShippingMethod::factory()->create();

    $order = app(CheckoutService::class)->place($cart, checkoutPayload());
    $payment = $order->payments()->firstOrFail();

    $this->postJson('/webhooks/payments/fake', [
        'id' => $payment->gateway_reference,
        'status' => 'paid',
    ], ['X-Api-Key' => 'testing-webhook-key'])->assertOk();

    Http::assertNothingSent();
});

it('does not call Meta when the access token is missing', function () {
    Mail::fake();
    Http::preventStrayRequests();
    Http::fake();

    app(MetaAdsSettings::class)->update([
        'enabled' => true,
        'pixel_id' => '123456789012345',
        'access_token' => '',
    ]);

    [$cart] = stockedCart();
    ShippingMethod::factory()->create();

    $order = app(CheckoutService::class)->place($cart, checkoutPayload());
    $payment = $order->payments()->firstOrFail();

    $this->postJson('/webhooks/payments/fake', [
        'id' => $payment->gateway_reference,
        'status' => 'paid',
    ], ['X-Api-Key' => 'testing-webhook-key'])->assertOk();

    Http::assertNothingSent();
});

it('does not send a second purchase event for a duplicate paid webhook', function () {
    Mail::fake();
    Http::preventStrayRequests();
    Http::fake([
        'https://graph.facebook.com/*' => Http::response(['events_received' => 1]),
    ]);

    enableMetaAds();

    [$cart] = stockedCart();
    ShippingMethod::factory()->create();

    $order = app(CheckoutService::class)->place($cart, checkoutPayload());
    $payment = $order->payments()->firstOrFail();
    $payload = ['id' => $payment->gateway_reference, 'status' => 'paid'];

    $this->postJson('/webhooks/payments/fake', $payload, ['X-Api-Key' => 'testing-webhook-key'])->assertOk();
    $this->postJson('/webhooks/payments/fake', $payload, ['X-Api-Key' => 'testing-webhook-key'])->assertOk();

    Http::assertSentCount(1);
});
