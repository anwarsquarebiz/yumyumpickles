<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Resources\CartResource;
use App\Services\Cart\CartResolver;
use App\Services\Cart\CartService;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function __construct(
        private readonly CartResolver $resolver,
        private readonly CartService $carts,
    ) {}

    public function show(): Response
    {
        $cart = $this->resolver->current();

        return Inertia::render('storefront/cart', [
            'cartDetail' => $cart === null
                ? null
                : new CartResource($cart, $this->carts->totals($cart)),
            'seo' => [
                'title' => 'Your cart',
                'description' => 'Review your homemade pickle jars, apply coupons, and checkout securely.',
            ],
        ]);
    }
}
