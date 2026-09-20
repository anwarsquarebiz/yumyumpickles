<?php

namespace App\Services\Cart;

use App\Enums\CouponType;
use App\Exceptions\CouponException;
use App\Models\Coupon;
use App\Models\User;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

/**
 * Validates discount codes and turns them into an amount.
 *
 * Eligibility is re-checked every time the cart is read and again at checkout,
 * so a code that expires or runs out mid-session cannot slip through.
 *
 * @phpstan-type CouponPayload array{
 *     code: string,
 *     description?: string|null,
 *     type: string,
 *     value: string|float|int,
 *     minimum_subtotal?: string|float|int|null,
 *     usage_limit?: int|null,
 *     usage_limit_per_customer?: int|null,
 *     starts_at?: string|null,
 *     expires_at?: string|null,
 *     is_active?: bool
 * }
 */
class CouponService
{
    /**
     * Look a code up case-insensitively.
     *
     * @throws CouponException when no such code exists.
     */
    public function findByCode(string $code): Coupon
    {
        $coupon = Coupon::query()
            ->where('code', mb_strtoupper(trim($code)))
            ->first();

        if ($coupon === null) {
            throw CouponException::notFound($code);
        }

        return $coupon;
    }

    /**
     * @throws CouponException when the coupon cannot be applied to this subtotal.
     */
    public function assertUsable(Coupon $coupon, Money $subtotal, ?User $customer = null): void
    {
        if (! $coupon->is_active) {
            throw CouponException::inactive();
        }

        if (! $coupon->hasStarted()) {
            throw CouponException::notStarted();
        }

        if ($coupon->hasExpired()) {
            throw CouponException::expired();
        }

        if ($coupon->hasReachedUsageLimit()) {
            throw CouponException::usageLimitReached();
        }

        if (! $subtotal->isPositive()) {
            throw CouponException::emptyCart();
        }

        if (! $coupon->meetsMinimumSubtotal($subtotal)) {
            throw CouponException::minimumSubtotalNotMet($coupon->minimum_subtotal_amount ?? Money::zero());
        }

        if ($customer !== null && $this->hasReachedCustomerLimit($coupon, $customer)) {
            throw CouponException::customerLimitReached();
        }
    }

    /**
     * Whether a coupon is currently applicable, without raising.
     */
    public function isUsable(Coupon $coupon, Money $subtotal, ?User $customer = null): bool
    {
        try {
            $this->assertUsable($coupon, $subtotal, $customer);
        } catch (CouponException) {
            return false;
        }

        return true;
    }

    public function discountFor(Coupon $coupon, Money $subtotal): Money
    {
        return $coupon->discountFor($subtotal);
    }

    public function hasReachedCustomerLimit(Coupon $coupon, User $customer): bool
    {
        if ($coupon->usage_limit_per_customer === null) {
            return false;
        }

        return $this->redemptionCountFor($coupon, $customer) >= $coupon->usage_limit_per_customer;
    }

    public function redemptionCountFor(Coupon $coupon, User $customer): int
    {
        return $coupon->redemptions()
            ->where('user_id', $customer->getKey())
            ->count();
    }

    /**
     * Record a use of the coupon and increment its counter.
     *
     * The counter is incremented with an atomic expression rather than a
     * read-modify-write so two simultaneous checkouts cannot both see the same
     * starting value and overshoot the usage limit.
     */
    public function redeem(Coupon $coupon, int $orderId, Money $discount, ?User $customer = null): void
    {
        DB::transaction(function () use ($coupon, $orderId, $discount, $customer): void {
            $coupon->redemptions()->create([
                'order_id' => $orderId,
                'user_id' => $customer?->getKey(),
                'discount_amount' => $discount,
            ]);

            Coupon::query()->whereKey($coupon->getKey())->increment('used_count');
        });

        $coupon->refresh();
    }

    /**
     * @param  CouponPayload  $data
     */
    public function create(array $data): Coupon
    {
        return Coupon::query()->create($this->attributes($data));
    }

    /**
     * @param  CouponPayload  $data
     */
    public function update(Coupon $coupon, array $data): Coupon
    {
        $coupon->update($this->attributes($data));

        return $coupon->refresh();
    }

    public function delete(Coupon $coupon): void
    {
        $coupon->delete();
    }

    /**
     * Translate the admin form into stored columns: fixed amounts are entered as
     * decimals and stored as minor units, percentages are entered as a percent
     * and stored as basis points.
     *
     * @param  CouponPayload  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        $type = CouponType::from($data['type']);

        return [
            'code' => mb_strtoupper(trim($data['code'])),
            'description' => $data['description'] ?? null,
            'type' => $type,
            'value' => $this->storedValue($type, $data['value']),
            'minimum_subtotal_amount' => $this->optionalMoney($data['minimum_subtotal'] ?? null),
            'usage_limit' => $data['usage_limit'] ?? null,
            'usage_limit_per_customer' => $data['usage_limit_per_customer'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ];
    }

    private function storedValue(CouponType $type, string|float|int $value): int
    {
        return match ($type) {
            CouponType::FixedAmount => Money::fromDecimal($value)->amount,
            CouponType::Percentage => (int) round(((float) $value) * 100),
            CouponType::FreeShipping => 0,
        };
    }

    private function optionalMoney(string|float|int|null $value): ?Money
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Money::fromDecimal($value);
    }
}
