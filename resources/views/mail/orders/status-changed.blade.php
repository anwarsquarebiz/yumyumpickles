# Order {{ $order->order_number }} update

Your order is now **{{ $order->status->label() }}**.

[View your order]({{ $url }})
