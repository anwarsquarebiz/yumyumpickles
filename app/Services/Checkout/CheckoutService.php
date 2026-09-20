<?php

namespace App\Services\Checkout;

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\CheckoutException;
use App\Exceptions\CouponException;
use App\Exceptions\PaymentGatewayException;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Ads\MetaAttribution;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use App\Services\Cart\CouponService;
use App\Services\Catalog\InventoryService;
use App\Services\Orders\OrderNumberGenerator;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Settings\SettingsService;
use App\Support\CheckoutTotals;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Turns a cart into a pending order and starts payment.
 *
 * Inventory is reserved here, even before the customer pays, so two overlapping
 * checkouts cannot both take the last unit. A failed or cancelled payment
 * releases that reservation.
 *
 * @phpstan-type CheckoutPayload array{
 *     email: string,
 *     phone?: string|null,
 *     shipping: array<string, mixed>,
 *     billing?: array<string, mixed>|null,
 *     billing_same_as_shipping?: bool,
 *     shipping_method_id: int,
 *     customer_note?: string|null,
 *     save_address?: bool
 * }
 */
class CheckoutService
{
    public function __construct(
        private readonly CartService $carts,
        private readonly CartResolver $resolver,
        private readonly CouponService $coupons,
        private readonly ShippingCalculator $shipping,
        private readonly AddressService $addresses,
        private readonly InventoryService $inventory,
        private readonly OrderNumberGenerator $numbers,
        private readonly PaymentGatewayManager $gateways,
        private readonly SettingsService $settings,
        private readonly MetaAttribution $attribution,
    ) {}

    /**
     * @param  CheckoutPayload  $payload
     */
    public function place(Cart $cart, array $payload, ?User $customer = null): Order
    {
        if ($customer === null && ! $this->settings->get('checkout.guest_checkout_enabled', true)) {
            throw CheckoutException::guestCheckoutDisabled();
        }

        $cart->loadMissing(['items.variant.product', 'coupon', 'user']);

        if ($cart->isEmpty()) {
            throw CheckoutException::emptyCart();
        }

        if ($this->carts->pruneUnavailable($cart) > 0 || $cart->fresh('items')?->isEmpty()) {
            throw CheckoutException::unavailableItems();
        }

        $method = $this->shipping->findActive((int) $payload['shipping_method_id']);

        if ($method === null) {
            throw CheckoutException::shippingUnavailable();
        }

        $totals = $this->totals($cart, $method, $payload['payment_method'] ?? null);
        $shippingAddress = $this->snapshot($payload['shipping']);
        $billingAddress = ($payload['billing_same_as_shipping'] ?? true)
            ? $shippingAddress
            : $this->snapshot($payload['billing'] ?? $payload['shipping']);

        $order = DB::transaction(function () use ($cart, $payload, $customer, $method, $totals, $shippingAddress, $billingAddress): Order {
            $order = Order::query()->create([
                'order_number' => $this->numbers->next(),
                'user_id' => $customer?->getKey(),
                'email' => $payload['email'],
                'phone' => $payload['phone'] ?? $shippingAddress['phone'] ?? null,
                'status' => OrderStatus::Pending,
                'currency' => $cart->currency,
                'subtotal_amount' => $totals->subtotal,
                'discount_amount' => $totals->discount,
                'shipping_amount' => $totals->shipping,
                'tax_amount' => $totals->tax,
                'grand_total_amount' => $totals->grandTotal,
                'refunded_amount' => Money::zero($cart->currency),
                'coupon_id' => $cart->coupon_id,
                'coupon_code' => $totals->couponCode,
                'shipping_address' => $shippingAddress,
                'billing_address' => $billingAddress,
                'shipping_method_name' => $method->name,
                'customer_note' => $payload['customer_note'] ?? null,
                'ads_attribution' => $this->attribution->capture(),
                'placed_at' => now(),
            ]);

            $this->writeItems($order, $cart);
            $this->reserveStock($order);
            $this->redeemCoupon($order, $cart, $totals);

            $order->recordEvent(OrderStatus::Pending->value, 'Order placed.', $customer, fromStatus: null);

            $this->startPayment($order, $payload['payment_method'] ?? null);

            if ($customer !== null && ($payload['save_address'] ?? false)) {
                $this->addresses->remember($customer, $shippingAddress, AddressType::Shipping);
            }

            $this->awardLoyalty($order, $customer);
            $this->carts->clear($cart);
            $this->resolver->forgetToken();

            return $order->load(['items', 'payments', 'statusEvents']);
        });

        return $order;
    }

    public function totals(Cart $cart, ShippingMethod $method, ?string $paymentMethod = null): CheckoutTotals
    {
        $cartTotals = $this->carts->totals($cart);
        $shipping = $this->shipping->quote(
            $method,
            $cartTotals->total(),
            $this->shipping->weightKg($cart),
        );

        if ($cartTotals->freeShipping) {
            $shipping = Money::zero($cart->currency);
        }

        $taxable = $cartTotals->total()->plus($shipping);
        $tax = $taxable->percentage((int) $this->settings->get('checkout.tax_rate_basis_points', 0));

        return new CheckoutTotals(
            subtotal: $cartTotals->subtotal,
            discount: $cartTotals->discount,
            shipping: $shipping,
            tax: $tax,
            grandTotal: $taxable->plus($tax),
            couponCode: $cartTotals->couponCode,
        );
    }

    public function redirectUrl(Order $order): string
    {
        $payment = $order->latestPayment ?? $order->payments->last();

        return $payment?->redirect_url ?: route('checkout.complete', $order);
    }

    private function writeItems(Order $order, Cart $cart): void
    {
        $discount = $this->carts->totals($cart)->discount;

        foreach ($cart->items as $item) {
            $variant = $item->variant;
            $line = $item->lineTotal();

            $order->items()->create([
                'product_id' => $variant?->product_id,
                'product_variant_id' => $variant?->getKey(),
                'product_title' => $variant?->product?->title ?? 'Unavailable product',
                'variant_title' => $variant?->displayTitle(),
                'sku' => $variant?->sku,
                'unit_price_amount' => $item->unitPrice(),
                'quantity' => $item->quantity,
                'subtotal_amount' => $line,
                'discount_amount' => Money::zero($cart->currency),
                'total_amount' => $line,
            ]);
        }

        if ($discount->isPositive() && $order->items()->exists()) {
            $first = $order->items()->oldest('id')->first();
            $first?->forceFill([
                'discount_amount' => $discount,
                'total_amount' => $first->lineTotal()->minus($discount)->atLeastZero(),
            ])->save();
        }
    }

    private function reserveStock(Order $order): void
    {
        $order->loadMissing('items.variant');

        foreach ($order->items as $item) {
            if ($item->variant === null) {
                continue;
            }

            $this->inventory->reserve($item->variant, $item->quantity, $item);
        }
    }

    private function redeemCoupon(Order $order, Cart $cart, CheckoutTotals $totals): void
    {
        if ($cart->coupon === null) {
            return;
        }

        if (! $totals->discount->isPositive() && $cart->coupon->type !== \App\Enums\CouponType::FreeShipping) {
            return;
        }

        try {
            $this->coupons->assertUsable($cart->coupon, $totals->subtotal, $cart->user);
        } catch (CouponException $exception) {
            throw CheckoutException::paymentFailed($exception->getMessage());
        }

        $this->coupons->redeem($cart->coupon, $order->id, $totals->discount, $cart->user);
    }

    private function startPayment(Order $order, ?string $paymentMethod = null): Payment
    {
        $gatewayName = $this->gatewayFor($paymentMethod);

        try {
            $payment = $order->payments()->create([
                'gateway' => $gatewayName,
                'status' => PaymentStatus::Pending,
                'amount' => $order->grandTotal(),
                'currency' => $order->currency,
            ]);

            $initiation = $this->gateways->driver($gatewayName)->initiate($payment->load('order'));

            if (blank($initiation->redirectUrl) && $initiation->status !== PaymentStatus::Paid) {
                throw CheckoutException::paymentFailed('The payment gateway did not return a payment page.');
            }

            $payment->forceFill([
                'gateway_reference' => $initiation->reference,
                'status' => $initiation->status,
                'redirect_url' => $initiation->redirectUrl,
                'response_payload' => $initiation->payload,
                'paid_at' => $initiation->status === PaymentStatus::Paid ? now() : null,
            ])->save();
        } catch (PaymentGatewayException $exception) {
            throw CheckoutException::paymentFailed($exception->getMessage());
        }

        return $payment;
    }

    private function gatewayFor(?string $paymentMethod): string
    {
        $method = strtolower((string) $paymentMethod);

        if (in_array($method, ['cod', 'cash', 'manual'], true)) {
            return 'manual';
        }

        if (in_array($method, ['upi', 'cards', 'card', 'netbanking', 'online'], true)) {
            return (string) config('payments.default', 'manual');
        }

        return (string) config('payments.default', 'manual');
    }

    private function awardLoyalty(Order $order, ?User $customer): void
    {
        if ($customer === null) {
            return;
        }

        $points = intdiv(max(0, $order->grandTotal()->amount), 1000);

        if ($points < 1) {
            return;
        }

        $customer->forceFill([
            'loyalty_points' => $customer->loyalty_points + $points,
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function snapshot(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $parts = $name === '' ? [] : (preg_split('/\s+/', $name, 2) ?: []);

        return [
            'first_name' => $input['first_name'] ?? ($parts[0] ?? ''),
            'last_name' => $input['last_name'] ?? ($parts[1] ?? ''),
            'company' => $input['company'] ?? null,
            'address_line1' => $input['address_line1'] ?? $input['line1'] ?? '',
            'address_line2' => $input['address_line2'] ?? $input['line2'] ?? null,
            'city' => $input['city'] ?? '',
            'province' => $input['province'] ?? $input['state'] ?? null,
            'postal_code' => $input['postal_code'] ?? $input['pincode'] ?? '',
            'country_code' => strtoupper((string) ($input['country_code'] ?? 'IN')),
            'phone' => $input['phone'] ?? null,
        ];
    }
}
