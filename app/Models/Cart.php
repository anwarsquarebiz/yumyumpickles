<?php

namespace App\Models;

use Database\Factories\CartFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Cart extends Model
{
    /** @use HasFactory<CartFactory> */
    use HasFactory;

    protected $fillable = [
        'token',
        'user_id',
        'coupon_id',
        'currency',
        'last_activity_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $cart): void {
            $cart->token ??= (string) Str::uuid();
            $cart->currency ??= (string) config('shop.currency.code', 'USD');
            $cart->last_activity_at ??= now();
        });
    }

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Coupon, $this>
     */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * Total units across every line, used for the header badge.
     */
    public function itemCount(): int
    {
        return (int) $this->items->sum('quantity');
    }

    public function belongsToGuest(): bool
    {
        return $this->user_id === null;
    }

    public function touchActivity(): void
    {
        $this->forceFill(['last_activity_at' => now()])->save();
    }

    /**
     * Carts that have not been touched within the configured lifetime and can
     * therefore be pruned.
     *
     * @param  Builder<Cart>  $query
     */
    public function scopeAbandoned(Builder $query): void
    {
        $query->where(
            'last_activity_at',
            '<',
            now()->subDays((int) config('shop.cart.lifetime_days', 30)),
        );
    }
}
