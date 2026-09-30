<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShippingMethod;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\RazorpaySettings;
use App\Support\Money;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

function enableRazorpay(string $mode = 'test'): void
{
    app(RazorpaySettings::class)->update([
        'enabled' => true,
        'mode' => $mode,
        'test' => ['key_id' => 'rzp_test_abc123', 'key_secret' => 'test-secret', 'webhook_secret' => 'test-hook'],
        'live' => ['key_id' => 'rzp_live_xyz789', 'key_secret' => 'live-secret', 'webhook_secret' => 'live-hook'],
    ]);
}

function fakeRazorpayOrder(string $id = 'order_TEST123'): void
{
    Http::fake([
        'api.razorpay.com/v1/orders' => Http::response(['id' => $id, 'entity' => 'order', 'status' => 'created']),
    ]);
}

function placeRazorpayOrder(): Order
{
    [$cart] = stockedCart();
    $method = ShippingMethod::factory()->create();

    test()->withCookie('cart_token', $cart->token)
        ->post('/checkout', checkoutPayload([
            'shipping_method_id' => $method->id,
            'payment_method' => 'razorpay',
        ]));

    return Order::query()->with('payments')->latest('id')->firstOrFail();
}

/**
 * @param  array<string, mixed>  $payload
 */
function postRazorpayWebhook(array $payload, string $secret = 'test-hook'): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);

    return test()->call('POST', '/webhooks/payments/razorpay', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_RAZORPAY_SIGNATURE' => hash_hmac('sha256', $body, $secret),
    ], content: $body);
}

/**
 * @return array<string, mixed>
 */
function capturedEvent(Payment $payment, string $event = 'payment.captured', ?int $amount = null): array
{
    return [
        'entity' => 'event',
        'event' => $event,
        'payload' => [
            'payment' => ['entity' => [
                'id' => 'pay_ABC123',
                'order_id' => $payment->gateway_reference,
                'amount' => $amount ?? $payment->money()->amount,
                'currency' => $payment->currency,
                'status' => 'captured',
            ]],
        ],
    ];
}

it('offers razorpay at checkout only once it is enabled with keys', function () {
    [$cart] = stockedCart();
    ShippingMethod::factory()->create();

    $this->withCookie('cart_token', $cart->token)->get('/checkout')
        ->assertInertia(fn ($page) => $page
            ->has('payment_methods', 1)
            ->where('payment_methods.0.value', 'cod'));

    enableRazorpay();

    $this->withCookie('cart_token', $cart->token)->get('/checkout')
        ->assertInertia(fn ($page) => $page
            ->has('payment_methods', 2)
            ->where('payment_methods.0.value', 'razorpay')
            ->where('payment_methods.1.value', 'cod'));
});

it('rejects razorpay as a payment method while it is disabled', function () {
    [$cart] = stockedCart();
    $method = ShippingMethod::factory()->create();

    $this->withCookie('cart_token', $cart->token)
        ->post('/checkout', checkoutPayload(['shipping_method_id' => $method->id, 'payment_method' => 'razorpay']))
        ->assertSessionHasErrors('payment_method');

    expect(Order::query()->count())->toBe(0);
});

it('places cash on delivery orders on the manual gateway', function () {
    [$cart] = stockedCart();
    $method = ShippingMethod::factory()->create();

    $this->withCookie('cart_token', $cart->token)
        ->post('/checkout', checkoutPayload(['shipping_method_id' => $method->id, 'payment_method' => 'cod']))
        ->assertRedirect();

    expect(Order::query()->firstOrFail()->payments()->firstOrFail()->gateway)->toBe('manual');
});

it('creates a razorpay order and sends the customer to the pay page', function () {
    enableRazorpay();
    fakeRazorpayOrder();

    $order = placeRazorpayOrder();
    $payment = $order->payments->first();

    expect($payment->gateway)->toBe('razorpay')
        ->and($payment->gateway_reference)->toBe('order_TEST123')
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->redirect_url)->toBe(route('checkout.pay', $order))
        ->and($payment->request_payload['mode'])->toBe('test')
        ->and($payment->request_payload['key_id'])->toBe('rzp_test_abc123')
        ->and(json_encode($payment->request_payload))->not->toContain('test-secret');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.razorpay.com/v1/orders'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('rzp_test_abc123:test-secret'))
        && $request['amount'] === $order->grandTotal()->amount
        && $request['receipt'] === $order->order_number);

    $this->get(route('checkout.pay', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout-pay')
            ->where('razorpay.key', 'rzp_test_abc123')
            ->where('razorpay.order_id', 'order_TEST123')
            ->where('razorpay.amount', $order->grandTotal()->amount));
});

it('uses the live keys when live mode is selected', function () {
    enableRazorpay('live');
    fakeRazorpayOrder('order_LIVE1');

    $payment = placeRazorpayOrder()->payments->first();

    expect($payment->request_payload['mode'])->toBe('live');

    Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Basic '.base64_encode('rzp_live_xyz789:live-secret')));
});

it('shows a checkout error when razorpay rejects the order', function () {
    enableRazorpay();
    Http::fake([
        'api.razorpay.com/*' => Http::response(['error' => ['description' => 'Authentication failed']], 401),
    ]);

    [$cart] = stockedCart();
    $method = ShippingMethod::factory()->create();

    $this->withCookie('cart_token', $cart->token)
        ->from('/checkout')
        ->post('/checkout', checkoutPayload(['shipping_method_id' => $method->id, 'payment_method' => 'razorpay']))
        ->assertRedirect('/checkout')
        ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'Authentication failed'));

    expect(Order::query()->count())->toBe(0);
});

it('marks the order paid from a signed webhook and ignores duplicates', function () {
    Mail::fake();
    enableRazorpay();
    fakeRazorpayOrder();

    $order = placeRazorpayOrder();
    $payment = $order->payments->first();

    postRazorpayWebhook(capturedEvent($payment))->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid);

    $eventCount = $order->fresh()->statusEvents()->count();

    postRazorpayWebhook(capturedEvent($payment, 'order.paid'))->assertOk();

    expect($order->fresh()->statusEvents()->count())->toBe($eventCount);

    $this->get(route('checkout.pay', $order))->assertRedirect(route('checkout.complete', $order));
});

it('accepts webhooks signed with the other mode secret', function () {
    enableRazorpay();
    fakeRazorpayOrder();

    $payment = placeRazorpayOrder()->payments->first();

    postRazorpayWebhook(capturedEvent($payment), 'live-hook')->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

it('rejects a webhook with a bad signature', function () {
    enableRazorpay();
    fakeRazorpayOrder();

    $payment = placeRazorpayOrder()->payments->first();

    postRazorpayWebhook(capturedEvent($payment), 'wrong-secret')->assertUnauthorized();

    $this->postJson('/webhooks/payments/razorpay', capturedEvent($payment))->assertUnauthorized();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('ignores a capture whose amount does not match the order', function () {
    enableRazorpay();
    fakeRazorpayOrder();

    $payment = placeRazorpayOrder()->payments->first();

    postRazorpayWebhook(capturedEvent($payment, amount: 100))->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

it('keeps the order open after a failed attempt so the customer can retry', function () {
    enableRazorpay();
    fakeRazorpayOrder();

    $order = placeRazorpayOrder();
    $payment = $order->payments->first();

    $failed = capturedEvent($payment, 'payment.failed');
    $failed['payload']['payment']['entity']['status'] = 'failed';

    postRazorpayWebhook($failed)->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending);

    $this->get(route('checkout.pay', $order))->assertOk();
});

it('refunds the captured razorpay payment with the keys it was taken with', function () {
    enableRazorpay();
    fakeRazorpayOrder();

    $payment = placeRazorpayOrder()->payments->first();

    enableRazorpay('live');

    Http::fake([
        'api.razorpay.com/v1/orders/order_TEST123/payments' => Http::response(['items' => [
            ['id' => 'pay_FAILED', 'status' => 'failed'],
            ['id' => 'pay_ABC123', 'status' => 'captured'],
        ]]),
        'api.razorpay.com/v1/payments/pay_ABC123/refund' => Http::response(['id' => 'rfnd_1', 'status' => 'processed']),
    ]);

    $result = app(PaymentGatewayManager::class)->driver('razorpay')
        ->refund($payment->fresh(), Money::fromMinor(500, $payment->currency), 'Damaged jar');

    expect($result->status)->toBe(RefundStatus::Succeeded)
        ->and($result->reference)->toBe('rfnd_1');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://api.razorpay.com/v1/payments/pay_ABC123/refund'
        && $request['amount'] === 500
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('rzp_test_abc123:test-secret')));
});
