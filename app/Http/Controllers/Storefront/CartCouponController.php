<?php

namespace App\Http\Controllers\Storefront;

use App\Exceptions\CouponException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ApplyCouponRequest;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use Illuminate\Http\RedirectResponse;

class CartCouponController extends Controller
{
    public function __construct(
        private readonly CartResolver $resolver,
        private readonly CartService $carts,
    ) {}

    public function store(ApplyCouponRequest $request): RedirectResponse
    {
        $cart = $this->resolver->currentOrCreate();

        try {
            $this->carts->applyCoupon($cart, $request->code());
        } catch (CouponException $exception) {
            /*
             | Returned as a field error rather than a flash so the message lands
             | next to the code input the customer just used.
             */
            return back()->withErrors(['code' => $exception->getMessage()]);
        }

        return back()->with('success', "Discount code {$request->code()} applied.");
    }

    public function destroy(): RedirectResponse
    {
        $cart = $this->resolver->current();

        if ($cart !== null) {
            $this->carts->removeCoupon($cart);
        }

        return back()->with('success', 'Discount code removed.');
    }
}
