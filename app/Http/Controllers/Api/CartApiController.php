<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CartException;
use App\Exceptions\CouponException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartApiController extends Controller
{
    public function __construct(
        private readonly CartResolver $resolver,
        private readonly CartService $carts,
    ) {}

    public function show(): JsonResponse
    {
        $cart = $this->resolver->current();

        return $this->payload($cart);
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'string'],
            'weight' => ['nullable', 'string'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $variant = $this->findVariant($data['product_id'], $data['weight'] ?? null);
        $cart = $this->resolver->currentOrCreate();
        $this->carts->add($cart, $variant, (int) ($data['quantity'] ?? 1));

        return $this->payload($cart->fresh(['items.variant.product.images', 'items.variant.images', 'coupon', 'user']))
            ->header('X-Cart-Token', $cart->token);
    }

    public function update(Request $request, CartItem $item): JsonResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        $cart = $this->ownedCart($item);
        $this->carts->updateQuantity($cart, $item, (int) $data['quantity']);

        return $this->payload($cart->fresh(['items.variant.product.images', 'items.variant.images', 'coupon', 'user']));
    }

    public function destroy(CartItem $item): JsonResponse
    {
        $cart = $this->ownedCart($item);
        $this->carts->remove($cart, $item);

        return $this->payload($cart->fresh(['items.variant.product.images', 'items.variant.images', 'coupon', 'user']));
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        $cart = $this->resolver->currentOrCreate();

        try {
            $this->carts->applyCoupon($cart, $data['code']);
        } catch (CouponException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return $this->payload($cart->fresh(['items.variant.product.images', 'items.variant.images', 'coupon', 'user']));
    }

    public function removeCoupon(): JsonResponse
    {
        $cart = $this->resolver->current();

        if ($cart !== null) {
            $this->carts->removeCoupon($cart);
        }

        return $this->payload($cart?->fresh(['items.variant.product.images', 'items.variant.images', 'coupon', 'user']));
    }

    private function payload(?Cart $cart): JsonResponse
    {
        if ($cart === null) {
            return response()->json([
                'data' => [
                    'id' => null,
                    'token' => null,
                    'items' => [],
                    'totals' => \App\Support\CartTotals::empty()->toArray(),
                ],
            ]);
        }

        return response()->json([
            'data' => (new CartResource($cart, $this->carts->totals($cart)))->resolve() + [
                'token' => $cart->token,
            ],
        ]);
    }

    private function ownedCart(CartItem $item): Cart
    {
        $cart = $this->resolver->current();

        if ($cart === null || $item->cart_id !== $cart->id) {
            abort(404);
        }

        return $cart;
    }

    private function findVariant(string $productId, ?string $weight): ProductVariant
    {
        $product = Product::query()
            ->published()
            ->with('variants')
            ->where(fn ($query) => $query->where('slug', $productId)->orWhere('id', $productId))
            ->firstOrFail();

        if ($weight === null || $weight === '') {
            return $product->defaultVariant() ?? $product->variants->firstOrFail();
        }

        return $product->variants->first(
            fn (ProductVariant $variant): bool => $variant->option1 === $weight || $variant->title === $weight
        ) ?? $product->defaultVariant() ?? $product->variants->firstOrFail();
    }
}
