<?php

use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\OrderItem;

it('renders the order number, items, total and shipping address', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->for($order)->create([
        'product_title' => 'Aspirin 100mg',
        'quantity' => 2,
        'variant_title' => 'Default',
    ]);

    $mailable = new OrderConfirmationMail($order->fresh());

    $mailable->assertSeeInHtml('Thank you for your purchase!')
        ->assertSeeInHtml('Order summary')
        ->assertSeeInHtml($order->order_number)
        ->assertSeeInHtml('Aspirin 100mg × 2')
        ->assertSeeInHtml('Hi Ada')
        ->assertSeeInHtml('Ada Lovelace')
        ->assertSeeInHtml('Shipping address')
        ->assertSeeInHtml('View your order')
        ->assertSeeInHtml($order->grandTotal()->format())
        ->assertSeeInHtml('Subtotal')
        ->assertSeeInText($order->order_number)
        ->assertSeeInText('Aspirin 100mg');
});
