<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\PlaceOrderRequest;
use App\Http\Resources\AddressResource;
use App\Http\Resources\CartResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use App\Services\Checkout\ShippingCalculator;
use App\Services\Currency\CurrencyConverter;
use App\Services\Payments\CheckoutPaymentMethods;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Settings\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartResolver $resolver,
        private readonly CartService $carts,
        private readonly CheckoutService $checkout,
        private readonly ShippingCalculator $shipping,
        private readonly SettingsService $settings,
        private readonly CurrencyConverter $converter,
        private readonly CheckoutPaymentMethods $paymentMethods,
        private readonly PaymentGatewayManager $gateways,
    ) {}

    public function show(): Response|RedirectResponse
    {
        $cart = $this->resolver->current();

        if ($cart === null || $cart->isEmpty()) {
            return to_route('cart.show')->with('warning', 'Your cart is empty.');
        }

        $this->carts->pruneUnavailable($cart);
        $cart->refresh()->load(['items.variant.product.images', 'items.variant.images', 'coupon', 'user']);

        if ($cart->isEmpty()) {
            return to_route('cart.show')->with('warning', 'Some items are no longer available.');
        }

        $quotes = $this->shipping->quotes($cart);
        $user = request()->user();

        return Inertia::render('storefront/checkout', [
            'cartDetail' => new CartResource($cart, $this->carts->totals($cart)),
            'shipping_methods' => collect($quotes)->map(fn (array $quote): array => [
                'id' => $quote['method']->id,
                'name' => $quote['method']->name,
                'description' => $quote['method']->description,
                'amount' => $this->converter->present($quote['amount']),
            ])->values(),
            'addresses' => $user === null
                ? []
                : AddressResource::collection($user->addresses()->latest('id')->get()),
            'customer' => $user === null ? null : [
                'email' => $user->email,
                'phone' => $user->phone,
                'name' => $user->name,
            ],
            'tax_rate_basis_points' => (int) $this->settings->get('checkout.tax_rate_basis_points', 0),
            'guest_checkout_enabled' => (bool) $this->settings->get('checkout.guest_checkout_enabled', true),
            'payment_methods' => $this->paymentMethods->options(),
            'seo' => [
                'title' => 'Checkout',
                'description' => 'Complete your purchase.',
            ],
        ]);
    }

    public function store(PlaceOrderRequest $request): RedirectResponse|SymfonyResponse
    {
        $cart = $this->resolver->current();

        if ($cart === null || $cart->isEmpty()) {
            return to_route('cart.show')->with('error', 'Your cart is empty.');
        }

        try {
            $order = $this->checkout->place($cart, $request->validated(), $request->user());
        } catch (CheckoutException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $ids = $request->session()->get('placed_order_ids', []);
        $ids[] = $order->id;
        $request->session()->put('placed_order_ids', $ids);

        $redirectUrl = $this->checkout->redirectUrl($order);

        if ($this->isExternalRedirect($request, $redirectUrl)) {
            return Inertia::location($redirectUrl);
        }

        return redirect()->to($redirectUrl);
    }

    private function isExternalRedirect(Request $request, string $url): bool
    {
        if (! str_starts_with($url, 'http://') && ! str_starts_with($url, 'https://')) {
            return false;
        }

        $targetOrigin = parse_url($url, PHP_URL_SCHEME).'://'.parse_url($url, PHP_URL_HOST);
        $targetPort = parse_url($url, PHP_URL_PORT) ?? (parse_url($url, PHP_URL_SCHEME) === 'https' ? 443 : 80);
        $appPort = $request->getPort() ?: ($request->getScheme() === 'https' ? 443 : 80);

        $targetAuthority = rtrim($targetOrigin, '/').':'.$targetPort;
        $appAuthority = rtrim($request->getSchemeAndHttpHost(), '/').':'.$appPort;

        return strcasecmp($targetAuthority, $appAuthority) !== 0;
    }

    public function complete(Order $order): Response
    {
        $this->assertVisible($order);

        $order->load(['items', 'payments', 'statusEvents.user']);

        return Inertia::render('storefront/checkout-complete', [
            'order' => new OrderResource($order),
            'seo' => [
                'title' => "Order {$order->order_number}",
                'description' => 'Thank you for your order.',
            ],
        ]);
    }

    /**
     * Opens Razorpay's checkout modal for a pending online payment. Once the
     * order is no longer awaiting payment the customer goes to the complete page.
     */
    public function pay(Order $order): Response|RedirectResponse
    {
        $this->assertVisible($order);

        $payment = $order->payments()
            ->where('gateway', 'razorpay')
            ->where('status', PaymentStatus::Pending->value)
            ->latest('id')
            ->first();

        if ($payment === null || $order->status !== OrderStatus::Pending) {
            return to_route('checkout.complete', $order);
        }

        $shipping = (array) $order->shipping_address;

        return Inertia::render('storefront/checkout-pay', [
            'order_number' => $order->order_number,
            'grand_total' => $order->grandTotal(),
            'callback_url' => route('checkout.callback', $order),
            'complete_url' => route('checkout.complete', $order),
            'checkout_script' => (string) config('payments.gateways.razorpay.checkout_script'),
            'razorpay' => [
                'key' => $this->gateways->razorpay()->checkoutKeyId($payment),
                'order_id' => $payment->gateway_reference,
                'amount' => $payment->money()->amount,
                'currency' => $payment->currency,
                'name' => (string) $this->settings->get('store.name'),
                'description' => "Order {$order->order_number}",
                'prefill' => [
                    'name' => trim(($shipping['first_name'] ?? '').' '.($shipping['last_name'] ?? '')),
                    'email' => $order->email,
                    'contact' => (string) ($order->phone ?? ''),
                ],
                'notes' => ['order_number' => $order->order_number],
            ],
            'seo' => [
                'title' => 'Complete payment',
                'description' => "Pay for order {$order->order_number}.",
            ],
        ]);
    }

    public function callback(Order $order): RedirectResponse
    {
        $this->assertVisible($order);

        return to_route('checkout.complete', $order)
            ->with('success', $order->status->isPaid()
                ? 'Payment received. Thank you for your order.'
                : 'We have received your order and are waiting for payment confirmation.');
    }

    private function assertVisible(Order $order): void
    {
        if (request()->hasValidSignature()) {
            return;
        }

        $user = request()->user();

        if ($user?->isAdmin()) {
            return;
        }

        if ($user === null) {
            abort_unless(in_array($order->id, session('placed_order_ids', []), true), 403);

            return;
        }

        abort_unless($order->user_id === $user->getKey(), 403);
    }
}
