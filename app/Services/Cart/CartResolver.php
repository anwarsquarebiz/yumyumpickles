<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Finds the cart belonging to the current visitor.
 *
 * Guests are tracked with an opaque token in an encrypted cookie; signed-in
 * customers own their cart by user id. Keeping this out of CartService means the
 * service itself stays free of request state and is straightforward to test.
 */
class CartResolver
{
    /**
     * Relations every cart read needs, loaded up front because lazy loading is
     * disabled outside production.
     *
     * @var array<int, string>
     */
    private const Relations = ['items.variant.product.images', 'items.variant.images', 'coupon', 'user'];

    public function __construct(
        private readonly Request $request,
        private readonly CartService $carts,
    ) {}

    /**
     * The visitor's cart, or null when they have never added anything.
     */
    public function current(): ?Cart
    {
        $user = $this->request->user();

        if ($user !== null) {
            return $this->userCart($user);
        }

        $token = $this->token();

        if ($token === null) {
            return null;
        }

        return Cart::query()
            ->with(self::Relations)
            ->whereNull('user_id')
            ->where('token', $token)
            ->first();
    }

    /**
     * The visitor's cart, created on demand. Called only by mutations, so merely
     * browsing the store never writes a row.
     */
    public function currentOrCreate(): Cart
    {
        $cart = $this->current();

        if ($cart !== null) {
            return $cart;
        }

        $user = $this->request->user();

        $cart = Cart::query()->create([
            'user_id' => $user?->getKey(),
            'currency' => config('shop.currency.code', 'USD'),
        ]);

        $this->rememberToken($cart);

        return $cart->load(self::Relations);
    }

    /**
     * Attach a guest cart to the customer who just signed in, merging it into any
     * cart they already had.
     */
    public function claimFor(User $user): ?Cart
    {
        $token = $this->token();
        $existing = $this->userCart($user);

        $guestCart = $token === null
            ? null
            : Cart::query()
                ->with('items.variant')
                ->whereNull('user_id')
                ->where('token', $token)
                ->first();

        if ($guestCart === null) {
            return $existing;
        }

        if ($existing === null) {
            $guestCart->forceFill(['user_id' => $user->getKey()])->save();

            return $guestCart->load(self::Relations);
        }

        return $this->carts->merge($guestCart, $existing);
    }

    /**
     * Forget the guest token, used after an order is placed.
     */
    public function forgetToken(): void
    {
        Cookie::queue(Cookie::forget($this->cookieName()));
    }

    private function userCart(User $user): ?Cart
    {
        return Cart::query()
            ->with(self::Relations)
            ->where('user_id', $user->getKey())
            ->latest('id')
            ->first();
    }

    private function token(): ?string
    {
        $header = $this->request->header('X-Cart-Token');

        if (is_string($header) && $header !== '') {
            return $header;
        }

        $token = $this->request->cookie($this->cookieName());

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function rememberToken(Cart $cart): void
    {
        Cookie::queue(
            $this->cookieName(),
            $cart->token,
            (int) config('shop.cart.lifetime_days', 30) * 24 * 60,
        );
    }

    private function cookieName(): string
    {
        return (string) config('shop.cart.cookie', 'cart_token');
    }
}
