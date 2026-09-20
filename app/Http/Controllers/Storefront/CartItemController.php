<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\CartException;
use App\Exceptions\InsufficientInventoryException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreCartItemRequest;
use App\Http\Requests\Storefront\UpdateCartItemRequest;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;

class CartItemController extends Controller
{
    public function __construct(
        private readonly CartResolver $resolver,
        private readonly CartService $carts,
    ) {}

    public function store(StoreCartItemRequest $request): RedirectResponse
    {
        $cart = $this->resolver->currentOrCreate();

        try {
            foreach ($request->lines() as $line) {
                $variant = ProductVariant::query()
                    ->with('product')
                    ->findOrFail($line['id']);

                $this->carts->add($cart, $variant, $line['qty']);
            }
        } catch (CartException|InsufficientInventoryException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('open_cart', true);
    }

    public function update(UpdateCartItemRequest $request, CartItem $item): RedirectResponse
    {
        $cart = $this->authorizedCart($item);

        $item->load('variant.product');

        try {
            $this->carts->updateQuantity($cart, $item, $request->quantity());
        } catch (CartException|InsufficientInventoryException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(CartItem $item): RedirectResponse
    {
        $cart = $this->authorizedCart($item);

        $this->carts->remove($cart, $item);

        return back()->with('success', 'Item removed from your cart.');
    }

    /**
     * Cart lines are addressed by id, so confirm the line really belongs to this
     * visitor before touching it.
     */
    private function authorizedCart(CartItem $item): Cart
    {
        $cart = $this->resolver->current();

        abort_if($cart === null || $item->cart_id !== $cart->getKey(), 404);

        return $cart;
    }
}
