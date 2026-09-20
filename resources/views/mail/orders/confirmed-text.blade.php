Thank you for your purchase!

@if ($order->customerFirstName())
Hi {{ $order->customerFirstName() }}, we're getting your order ready to be shipped. We will notify you when it has been sent.
@else
We're getting your order ready to be shipped. We will notify you when it has been sent.
@endif

Order {{ $order->order_number }}

Order summary
@foreach ($order->items as $item)
- {{ $item->product_title }} × {{ $item->quantity }}{{ $item->displayVariantTitle() ? ' ('.$item->displayVariantTitle().')' : '' }} — {{ $item->lineTotal()->format() }}
@endforeach

Subtotal: {{ $order->subtotal_amount->format() }}
@if ($order->discount_amount->isPositive())
Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}: − {{ $order->discount_amount->format() }}
@endif
Shipping: {{ $order->shipping_amount->format() }}
@if ($order->tax_amount->isPositive())
Taxes: {{ $order->tax_amount->format() }}
@endif
Total: {{ $order->grandTotal()->format() }} {{ $order->currency }}

@if ($order->addressLines($order->shipping_address) !== [])
Shipping address
{{ implode("\n", $order->addressLines($order->shipping_address)) }}
@endif

@if ($order->shipping_method_name)
Shipping method: {{ $order->shipping_method_name }}
@endif

Payment method: {{ $paymentMethod }} — {{ $order->grandTotal()->format() }}

View your order: {{ $url }}
Visit our store: {{ $shopUrl }}

If you have any questions, reply to this email or contact us at {{ $shopEmail }}.
