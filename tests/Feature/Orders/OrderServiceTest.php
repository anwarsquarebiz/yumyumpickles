<?php

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\InventoryService;
use App\Services\Orders\OrderService;
use App\Services\Orders\RefundService;
use App\Support\Money;
use Illuminate\Support\Facades\Mail;

it('transitions a paid order through fulfilment', function () {
    $order = Order::factory()->paid()->create();
    $service = app(OrderService::class);

    $service->transition($order, OrderStatus::Processing);
    $service->transition($order->fresh(), OrderStatus::Shipped);
    $service->transition($order->fresh(), OrderStatus::Delivered);

    expect($order->fresh()->status)->toBe(OrderStatus::Delivered)
        ->and($order->statusEvents()->count())->toBe(3);
});

it('refuses an illegal transition', function () {
    $order = Order::factory()->create();

    app(OrderService::class)->transition($order, OrderStatus::Delivered);
})->throws(InvalidOrderTransitionException::class);

it('issues a refund against a paid order', function () {
    $order = Order::factory()->paid()->create(['grand_total_amount' => 3099, 'refunded_amount' => 0]);
    Payment::factory()->paid()->for($order)->create(['amount' => 3099]);

    $refund = app(RefundService::class)->issue($order, Money::fromMinor(1000), reason: 'Damaged');

    expect($refund->status->value)->toBe('succeeded')
        ->and($order->fresh()->refunded_amount->amount)->toBe(1000)
        ->and($order->fresh()->status)->toBe(OrderStatus::Paid);
});

it('marks a fully refunded order as refunded', function () {
    $order = Order::factory()->paid()->create(['grand_total_amount' => 2000, 'refunded_amount' => 0]);
    Payment::factory()->paid()->for($order)->create(['amount' => 2000]);

    app(RefundService::class)->issue($order, Money::fromMinor(2000));

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

it('lists orders for staff and forbids customers', function () {
    Order::factory()->count(2)->create();
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();

    $this->actingAs($admin)->get('/admin/orders')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/orders/index')->has('orders.data', 2));

    $this->actingAs($customer)->get('/admin/orders')->assertForbidden();
});

it('shows checkout contact and address details on the admin order page', function () {
    $order = Order::factory()->create([
        'email' => 'ada@example.com',
        'phone' => '07000000000',
        'customer_note' => 'Please leave at the reception.',
        'shipping_method_name' => 'Standard shipping',
    ]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get("/admin/orders/{$order->id}")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/orders/show')
            ->where('order.data.email', 'ada@example.com')
            ->where('order.data.phone', '07000000000')
            ->where('order.data.customer_name', 'Ada Lovelace')
            ->where('order.data.customer_note', 'Please leave at the reception.')
            ->where('order.data.shipping_method_name', 'Standard shipping')
            ->where('order.data.shipping_address.address_line1', '1 Computing Lane')
            ->where('order.data.shipping_address_lines.0', 'Ada Lovelace')
            ->where('order.data.billing_address.city', 'London')
        );
});

it('lets staff update an order status', function () {
    $order = Order::factory()->paid()->create();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch("/admin/orders/{$order->id}/status", ['status' => OrderStatus::Processing->value])
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

it('queues a confirmation when staff mark a pending order paid', function () {
    Mail::fake();

    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create();

    app(OrderService::class)->transition($order, OrderStatus::Paid);

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);

    Mail::assertQueued(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order): bool {
        return $mail->hasTo($order->email) && $mail->order->is($order);
    });
    Mail::assertNotQueued(OrderStatusChangedMail::class);
});

it('queues a status change mail when a paid order moves to processing', function () {
    Mail::fake();

    $order = Order::factory()->paid()->create();

    app(OrderService::class)->transition($order, OrderStatus::Processing);

    Mail::assertQueued(OrderStatusChangedMail::class, function (OrderStatusChangedMail $mail) use ($order): bool {
        return $mail->hasTo($order->email) && $mail->order->is($order);
    });
    Mail::assertNotQueued(OrderConfirmationMail::class);
});

it('releases inventory when an order is cancelled', function () {
    $variant = ProductVariant::factory()->for(Product::factory())->withStock(5)->create();
    $order = Order::factory()->create();
    $item = OrderItem::factory()->for($order)->create([
        'product_id' => $variant->product_id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    app(InventoryService::class)->reserve($variant, 2, $item);

    expect($variant->fresh()->inventory_quantity)->toBe(3);

    app(OrderService::class)->transition($order, OrderStatus::Cancelled);

    expect($variant->fresh()->inventory_quantity)->toBe(5);
});
