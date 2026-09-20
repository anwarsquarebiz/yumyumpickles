<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\CheckoutException;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Checkout\CheckoutService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

it('places an order, reserves stock and starts a fake payment', function () {
    [$cart, $variant] = stockedCart(4000, 2, 5);
    ShippingMethod::factory()->create();

    $order = app(CheckoutService::class)->place($cart, checkoutPayload());

    expect($order->email)->toBe('buyer@example.com')
        ->and($order->items)->toHaveCount(1)
        ->and($order->payments)->toHaveCount(1)
        ->and($order->payments->first()->gateway)->toBe('fake')
        ->and($variant->fresh()->inventory_quantity)->toBe(3)
        ->and($cart->fresh()->items)->toHaveCount(0)
        ->and($order->grandTotal()->amount)->toBeGreaterThan(0);
});

it('marks the order paid when the webhook arrives and ignores duplicates', function () {
    Mail::fake();

    [$cart] = stockedCart();
    ShippingMethod::factory()->create();

    $order = app(CheckoutService::class)->place($cart, checkoutPayload());
    $payment = $order->payments()->firstOrFail();

    $payload = ['id' => $payment->gateway_reference, 'status' => 'paid'];

    $this->postJson('/webhooks/payments/fake', $payload, ['X-Api-Key' => 'testing-webhook-key'])
        ->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid);

    Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order): bool {
        return $mail->hasTo($order->email) && $mail->order->is($order);
    });

    $eventCount = $order->fresh()->statusEvents()->count();

    $this->postJson('/webhooks/payments/fake', $payload, ['X-Api-Key' => 'testing-webhook-key'])
        ->assertOk();

    expect($order->fresh()->statusEvents()->count())->toBe($eventCount)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid);

    Mail::assertQueued(OrderConfirmationMail::class, 1);
});

it('rejects a webhook without the api key', function () {
    $this->postJson('/webhooks/payments/fake', ['id' => 'x', 'status' => 'paid'])
        ->assertUnauthorized();
});

it('redirects an empty cart away from checkout', function () {
    $this->get('/checkout')->assertRedirect('/cart');
});

it('lets a customer see their own order and forbids someone else', function () {
    $owner = User::factory()->customer()->create();
    $order = Order::factory()->for($owner)->create();
    $stranger = User::factory()->customer()->create();

    $this->actingAs($owner)->get("/account/orders/{$order->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('storefront/account/orders/show'));

    $this->actingAs($stranger)->get("/account/orders/{$order->id}")->assertForbidden();
});

it('lets a guest view the complete page with a signed confirmation link', function () {
    $order = Order::factory()->guest()->create();

    $this->get(route('checkout.complete', $order))->assertForbidden();

    $this->get(URL::signedRoute('checkout.complete', $order))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('storefront/checkout-complete')
            ->where('order.data.is_paid', false)
            ->where('order.data.meta_event_id', null)
        );
});

it('posts checkout over HTTP and redirects to the complete page', function () {
    [$cart] = stockedCart();
    $method = ShippingMethod::factory()->create();

    $this->withCookie('cart_token', $cart->token)
        ->post('/checkout', checkoutPayload(['shipping_method_id' => $method->id]))
        ->assertRedirect();

    expect(Order::query()->count())->toBe(1);
});

function configureCustomGateway(string $createPath = '/payments'): void
{
    config([
        'payments.default' => 'custom',
        'payments.gateways.custom.base_url' => 'http://gateway.test/api/orders',
        'payments.gateways.custom.api_key' => 'testing-api-key',
        'payments.gateways.custom.endpoints.create_payment' => $createPath,
        'payments.gateways.custom.response_map.redirect_url' => 'payment_url',
        'payments.gateways.custom.retries' => 1,
    ]);
}

it('redirects checkout to the hosted payment page when the custom gateway returns payment_url', function () {
    Http::fake([
        'http://gateway.test/api/orders' => Http::response([
            'id' => 'pay_hosted_1',
            'status' => 'pending',
            'payment_url' => 'https://pay.example/hosted/abc',
        ], 201),
    ]);

    configureCustomGateway('');

    [$cart] = stockedCart();
    $method = ShippingMethod::factory()->create();

    $this->withCookie('cart_token', $cart->token)
        ->post('/checkout', checkoutPayload(['shipping_method_id' => $method->id]))
        ->assertRedirect('https://pay.example/hosted/abc');

    expect(Order::query()->count())->toBe(1)
        ->and(Order::query()->first()->payments()->first()->redirect_url)
        ->toBe('https://pay.example/hosted/abc');
});

it('does not send the customer to order complete when the custom gateway omits payment_url', function () {
    Http::fake([
        'http://gateway.test/api/orders' => Http::response([
            'id' => 'pay_no_url',
            'status' => 'pending',
        ], 201),
    ]);

    configureCustomGateway('');

    [$cart] = stockedCart();
    $method = ShippingMethod::factory()->create();

    $this->withCookie('cart_token', $cart->token)
        ->post('/checkout', checkoutPayload(['shipping_method_id' => $method->id]))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Order::query()->count())->toBe(0)
        ->and($cart->items()->count())->toBeGreaterThan(0);
});

it('posts to the base URL when the create path is empty instead of appending /payments', function () {
    Http::fake([
        'http://gateway.test/api/orders' => Http::response([
            'id' => 'pay_path_1',
            'status' => 'pending',
            'payment_url' => 'https://pay.example/hosted/path',
        ], 201),
        'http://gateway.test/api/orders/payments' => Http::response(['unexpected' => true], 500),
    ]);

    configureCustomGateway('');

    [$cart] = stockedCart();
    ShippingMethod::factory()->create();

    app(CheckoutService::class)->place($cart, checkoutPayload());

    Http::assertSent(fn ($request): bool => $request->url() === 'http://gateway.test/api/orders'
        && $request->method() === 'POST');
    Http::assertNotSent(fn ($request): bool => str_ends_with($request->url(), '/payments'));
});

it('surfaces a custom gateway outage as a checkout error instead of a 500', function () {
    Http::fake([
        'http://gateway.test/api/orders' => Http::failedConnection(),
    ]);

    configureCustomGateway('');

    [$cart] = stockedCart();
    ShippingMethod::factory()->create();

    expect(fn () => app(CheckoutService::class)->place($cart, checkoutPayload()))
        ->toThrow(CheckoutException::class);

    expect(Order::query()->count())->toBe(0);
});
