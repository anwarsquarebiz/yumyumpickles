<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CheckoutException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\ShippingMethod;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use App\Services\Checkout\CheckoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckoutApiController extends Controller
{
    public function __construct(
        private readonly CartResolver $resolver,
        private readonly CartService $carts,
        private readonly CheckoutService $checkout,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'payment' => ['required', 'string', 'max:32'],
            'address' => ['required', 'array'],
            'address.name' => ['required', 'string', 'max:150'],
            'address.phone' => ['required', 'string', 'max:32'],
            'address.line1' => ['required', 'string', 'max:255'],
            'address.city' => ['required', 'string', 'max:100'],
            'address.state' => ['required', 'string', 'max:100'],
            'address.pincode' => ['required', 'string', 'max:16'],
            'note' => ['nullable', 'string', 'max:1000'],
            'save_address' => ['boolean'],
        ]);

        $cart = $this->resolver->current();

        if ($cart === null || $cart->isEmpty()) {
            return response()->json(['message' => 'Your cart is empty.'], 422);
        }

        $method = ShippingMethod::query()->where('is_active', true)->orderBy('position')->first();

        if ($method === null) {
            return response()->json(['message' => 'Shipping is not available.'], 422);
        }

        try {
            $order = $this->checkout->place($cart, [
                'email' => $data['email'],
                'phone' => $data['address']['phone'],
                'shipping_method_id' => $method->id,
                'customer_note' => $data['note'] ?? null,
                'save_address' => $data['save_address'] ?? (bool) $request->user(),
                'billing_same_as_shipping' => true,
                'payment_method' => $data['payment'],
                'shipping' => $data['address'],
            ], $request->user());
        } catch (CheckoutException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        $order->load(['items', 'payments', 'statusEvents']);

        return response()->json([
            'data' => [
                'order' => new OrderResource($order),
                'redirect_url' => $this->checkout->redirectUrl($order),
            ],
        ], 201);
    }
}
