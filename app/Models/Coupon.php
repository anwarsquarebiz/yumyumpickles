<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\CouponType;
use App\Support\Money;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A discount code.
 *
 * `value` means different things per type: minor currency units for a fixed
 * amount, basis points for a percentage (1000 = 10%). Keeping both in one
 * integer column avoids a nullable column per type.
 */
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasFactory;

    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'minimum_subtotal_amount',
        'usage_limit',
        'usage_limit_per_customer',
        'used_count',
        'starts_at',
        'expires_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'minimum_subtotal_amount' => MoneyCast::class,
            'usage_limit' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $coupon): void {
            $coupon->code = mb_strtoupper(trim($coupon->code));
        });
    }

    /**
     * @return HasMany<CouponRedemption, $this>
     */
    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function hasStarted(): bool
    {
        return $this->starts_at === null || ! $this->starts_at->isFuture();
    }

    public function hasExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * Whether the code is redeemable at all, ignoring cart-specific rules.
     */
    public function isRedeemable(): bool
    {
        return $this->is_active
            && $this->hasStarted()
            && ! $this->hasExpired()
            && ! $this->hasReachedUsageLimit();
    }

    public function meetsMinimumSubtotal(Money $subtotal): bool
    {
        return $this->minimum_subtotal_amount === null
            || $subtotal->greaterThanOrEqualTo($this->minimum_subtotal_amount);
    }

    /**
     * The discount for a given subtotal, never more than the subtotal itself so
     * a generous fixed-amount code cannot produce a negative total.
     */
    public function discountFor(Money $subtotal): Money
    {
        if (! $subtotal->isPositive()) {
            return Money::zero($subtotal->currency);
        }

        $discount = match ($this->type) {
            CouponType::FixedAmount => Money::fromMinor($this->value, $subtotal->currency),
            CouponType::Percentage => $subtotal->percentage($this->value),
            CouponType::FreeShipping => Money::zero($subtotal->currency),
        };

        return $discount->cappedAt($subtotal);
    }

    /**
     * Human-readable value, for example "10%" or "$5.00".
     */
    public function displayValue(): string
    {
        return match ($this->type) {
            CouponType::FixedAmount => Money::fromMinor($this->value)->format(),
            CouponType::Percentage => rtrim(rtrim(number_format($this->value / 100, 2, '.', ''), '0'), '.').'%',
            CouponType::FreeShipping => 'Free shipping',
        };
    }

    /**
     * Percentage coupons are edited as a percentage but stored as basis points.
     */
    public function editableValue(): string
    {
        return match ($this->type) {
            CouponType::FixedAmount => Money::fromMinor($this->value)->toDecimal(),
            CouponType::Percentage => rtrim(rtrim(number_format($this->value / 100, 2, '.', ''), '0'), '.'),
            CouponType::FreeShipping => '0',
        };
    }

    /**
     * @param  Builder<Coupon>  $query
     */
    public function scopeRedeemable(Builder $query): void
    {
        $query->where('is_active', true)
            ->where(fn (Builder $query) => $query->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }
}
