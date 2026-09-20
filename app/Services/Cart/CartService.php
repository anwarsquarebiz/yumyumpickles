<?php

namespace App\Services\Cart;

use App\Exceptions\CartException;
use App\Exceptions\CouponException;
use App\Exceptions\InsufficientInventoryException;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\Catalog\InventoryService;
use App\Support\CartTotals;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Cart mutations and totals.
 *
 * Line prices are snapshotted when the line is created so a price change
 * mid-session cannot silently alter what the customer agreed to. Stock is only
 * checked here, never decremented: inventory moves when the order is placed.
 */
class CartService
{
    public function __construct(
        private readonly InventoryService $inventory,
        private readonly CouponService $coupons,
    ) {}

    /**
     * Add a variant, merging into the existing line when it is already present.
     *
     * @throws CartException when the variant is not purchasable.
     * @throws InsufficientInventoryException when stock cannot cover the line.
     */
    public function add(Cart $cart, ProductVariant $variant, int $quantity = 1): CartItem
    {
        $this->assertPurchasable($variant);

        $item = DB::transaction(function () use ($cart, $variant, $quantity): CartItem {
            $existing = $cart->items()
                ->where('product_variant_id', $variant->getKey())
                ->first();

            $requested = ($existing?->quantity ?? 0) + max(1, $quantity);
            $requested = $this->assertQuantityAllowed($variant, $requested);

            if ($existing !== null) {
                $existing->forceFill(['quantity' => $requested])->save();

                return $existing;
            }

            return $cart->items()->create([
                'product_variant_id' => $variant->getKey(),
                'quantity' => $requested,
                'unit_price_amount' => $variant->price(),
            ]);
        });

        $this->afterMutation($cart);

        return $item;
    }

    /**
     * Set an absolute quantity; zero or less removes the line.
     *
     * @throws InsufficientInventoryException
     */
    public function updateQuantity(Cart $cart, CartItem $item, int $quantity): void
    {
        if ($quantity < 1) {
            $this->remove($cart, $item);

            return;
        }

        $variant = $item->variant;

        $this->assertQuantityAllowed($variant, $quantity);

        $item->forceFill(['quantity' => $quantity])->save();

        $this->afterMutation($cart);
    }

    public function remove(Cart $cart, CartItem $item): void
    {
        $item->delete();

        $this->afterMutation($cart);
    }

    public function clear(Cart $cart): void
    {
        $cart->items()->delete();
        $cart->forceFill(['coupon_id' => null])->save();

        $this->afterMutation($cart);
    }

    /**
     * @throws CouponException when the code cannot be applied.
     */
    public function applyCoupon(Cart $cart, string $code): void
    {
        $coupon = $this->coupons->findByCode($code);

        $this->coupons->assertUsable($coupon, $this->subtotal($cart), $cart->user);

        $cart->forceFill(['coupon_id' => $coupon->getKey()])->save();

        $this->afterMutation($cart);
    }

    public function removeCoupon(Cart $cart): void
    {
        $cart->forceFill(['coupon_id' => null])->save();

        $this->afterMutation($cart);
    }

    public function subtotal(Cart $cart): Money
    {
        $cart->loadMissing('items');

        return $cart->items->reduce(
            fn (Money $carry, CartItem $item): Money => $carry->plus($item->lineTotal()),
            Money::zero($cart->currency),
        );
    }

    /**
     * Compute totals, silently dropping a coupon that is no longer eligible so a
     * customer is never shown a discount that checkout would refuse.
     */
    public function totals(Cart $cart): CartTotals
    {
        $cart->loadMissing(['items', 'coupon', 'user']);

        $subtotal = $this->subtotal($cart);
        $itemCount = $cart->itemCount();
        $quantityDiscount = Money::zero($cart->currency);
        $quantityLabel = null;
        $bogoDiscount = Money::zero($cart->currency);

        if ($itemCount === 2) {
            $quantityDiscount = $subtotal->percentage(500);
            $quantityLabel = 'Pair & save · 5% off';
        }

        if ($itemCount >= 3) {
            $unitPrices = [];

            foreach ($cart->items as $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    $unitPrices[] = $item->unitPrice()->amount;
                }
            }

            if ($unitPrices !== []) {
                $bogoDiscount = Money::fromMinor(min($unitPrices), $cart->currency);
            }
        }

        $promoDiscount = $quantityDiscount->plus($bogoDiscount);
        $afterPromo = $subtotal->minus($promoDiscount)->atLeastZero();
        $coupon = $cart->coupon;
        $couponDiscount = Money::zero($cart->currency);
        $couponCode = null;
        $couponLabel = null;
        $freeShipping = false;

        if ($coupon !== null && $this->coupons->isUsable($coupon, $afterPromo, $cart->user)) {
            $couponCode = $coupon->code;
            $couponLabel = $coupon->description ?: $coupon->displayValue();
            $couponDiscount = $this->coupons->discountFor($coupon, $afterPromo);
            $freeShipping = $coupon->type === \App\Enums\CouponType::FreeShipping;
        }

        $discount = $promoDiscount->plus($couponDiscount);
        $payable = $subtotal->minus($discount)->atLeastZero();

        return new CartTotals(
            subtotal: $subtotal,
            discount: $discount,
            itemCount: $itemCount,
            couponCode: $couponCode,
            quantityDiscount: $quantityDiscount,
            quantityLabel: $quantityLabel,
            bogoDiscount: $bogoDiscount,
            couponDiscount: $couponDiscount,
            couponLabel: $couponLabel,
            freeShipping: $freeShipping,
            pointsEarned: intdiv(max(0, $payable->amount), 1000),
        );
    }

    /**
     * Fold a guest cart into the customer's cart on login.
     *
     * Quantities are summed, capped by stock and the per-line limit, and the
     * guest cart is deleted so a stale cookie cannot resurrect it.
     */
    public function merge(Cart $guestCart, Cart $userCart): Cart
    {
        if ($guestCart->is($userCart)) {
            return $userCart;
        }

        $guestCart->loadMissing('items.variant');

        DB::transaction(function () use ($guestCart, $userCart): void {
            foreach ($guestCart->items as $item) {
                $existing = $userCart->items()
                    ->where('product_variant_id', $item->product_variant_id)
                    ->first();

                $variant = $item->variant;
                $quantity = ($existing?->quantity ?? 0) + $item->quantity;

                /*
                 | A merge must never fail: clamp instead of throwing, since the
                 | customer is mid-login and cannot act on an error here.
                 */
                $quantity = $this->clampQuantity($variant, $quantity);

                if ($quantity < 1) {
                    continue;
                }

                if ($existing !== null) {
                    $existing->forceFill(['quantity' => $quantity])->save();

                    continue;
                }

                $userCart->items()->create([
                    'product_variant_id' => $item->product_variant_id,
                    'quantity' => $quantity,
                    'unit_price_amount' => $item->unit_price_amount,
                ]);
            }

            if ($userCart->coupon_id === null && $guestCart->coupon_id !== null) {
                $userCart->forceFill(['coupon_id' => $guestCart->coupon_id])->save();
            }

            $guestCart->delete();
        });

        $this->afterMutation($userCart);

        return $userCart->load(['items.variant.product', 'coupon']);
    }

    /**
     * Drop lines whose variant or product is no longer purchasable, which is run
     * before checkout so an archived product cannot be ordered.
     *
     * @return int The number of lines removed.
     */
    public function pruneUnavailable(Cart $cart): int
    {
        $cart->loadMissing('items.variant.product');

        $removed = 0;

        foreach ($cart->items as $item) {
            $variant = $item->variant;

            if ($variant === null || ! $this->isPurchasable($variant) || ! $variant->canFulfill($item->quantity)) {
                $item->delete();
                $removed++;
            }
        }

        if ($removed > 0) {
            $cart->load('items.variant.product');
            $this->afterMutation($cart);
        }

        return $removed;
    }

    /**
     * @throws CartException
     */
    private function assertPurchasable(ProductVariant $variant): void
    {
        if (! $this->isPurchasable($variant)) {
            throw CartException::unavailableVariant($variant);
        }
    }

    private function isPurchasable(ProductVariant $variant): bool
    {
        $variant->loadMissing('product');

        return $variant->product !== null
            && $variant->product->isPublished()
            && $variant->isInStock();
    }

    /**
     * @throws InsufficientInventoryException when stock cannot cover the request.
     * @throws CartException when the per-line cap is exceeded.
     */
    private function assertQuantityAllowed(ProductVariant $variant, int $quantity): int
    {
        $cap = (int) config('shop.cart.max_quantity_per_line', 99);

        if ($quantity > $cap) {
            throw CartException::quantityTooHigh($cap);
        }

        $this->inventory->assertCanFulfill($variant, $quantity);

        return $quantity;
    }

    private function clampQuantity(?ProductVariant $variant, int $quantity): int
    {
        if ($variant === null) {
            return 0;
        }

        return max(0, min($quantity, $variant->purchasableQuantity()));
    }

    private function afterMutation(Cart $cart): void
    {
        $cart->touchActivity();
        $cart->load('items');
    }
}
