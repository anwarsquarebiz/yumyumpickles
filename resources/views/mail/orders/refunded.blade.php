# Refund for {{ $order->order_number }}

We've issued a refund of {{ $refund->money()->format() }} for your order.

@if ($refund->reason)
Reason: {{ $refund->reason }}
@endif
