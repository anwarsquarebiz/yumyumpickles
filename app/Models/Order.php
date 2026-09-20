<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'email',
        'phone',
        'status',
        'currency',
        'subtotal_amount',
        'discount_amount',
        'shipping_amount',
        'tax_amount',
        'grand_total_amount',
        'refunded_amount',
        'coupon_id',
        'coupon_code',
        'shipping_address',
        'billing_address',
        'shipping_method_name',
        'customer_note',
        'staff_note',
        'ads_attribution',
        'placed_at',
        'cancelled_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'subtotal_amount' => MoneyCast::class,
            'discount_amount' => MoneyCast::class,
            'shipping_amount' => MoneyCast::class,
            'tax_amount' => MoneyCast::class,
            'grand_total_amount' => MoneyCast::class,
            'refunded_amount' => MoneyCast::class,
            'shipping_address' => 'array',
            'billing_address' => 'array',
            'ads_attribution' => 'array',
            'placed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
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

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * @return HasMany<OrderStatusEvent, $this>
     */
    public function statusEvents(): HasMany
    {
        return $this->hasMany(OrderStatusEvent::class)->latest('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function grandTotal(): Money
    {
        return $this->grand_total_amount ?? Money::zero($this->currency);
    }

    public function refundedTotal(): Money
    {
        return $this->refunded_amount ?? Money::zero($this->currency);
    }

    public function refundableAmount(): Money
    {
        return $this->grandTotal()->minus($this->refundedTotal())->atLeastZero();
    }

    public function isFullyRefunded(): bool
    {
        return $this->refundedTotal()->greaterThanOrEqualTo($this->grandTotal())
            && $this->grandTotal()->isPositive();
    }

    /**
     * @throws InvalidOrderTransitionException
     */
    public function transitionTo(OrderStatus $status, ?User $actor = null, ?string $note = null): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw InvalidOrderTransitionException::from($this->status, $status);
        }

        $from = $this->status;

        $this->forceFill([
            'status' => $status,
            'cancelled_at' => $status === OrderStatus::Cancelled ? now() : $this->cancelled_at,
        ])->save();

        $this->statusEvents()->create([
            'from_status' => $from,
            'to_status' => $status,
            'note' => $note,
            'user_id' => $actor?->getKey(),
        ]);
    }

    public function recordEvent(string $toStatus, ?string $note = null, ?User $actor = null, ?string $fromStatus = null): OrderStatusEvent
    {
        return $this->statusEvents()->create([
            'from_status' => $fromStatus ?? $this->status->value,
            'to_status' => $toStatus,
            'note' => $note,
            'user_id' => $actor?->getKey(),
        ]);
    }

    public function customerFirstName(): ?string
    {
        $first = trim((string) ($this->shipping_address['first_name'] ?? ''));

        return $first === '' ? null : $first;
    }

    public function customerDisplayName(): ?string
    {
        $fromAddress = trim(($this->shipping_address['first_name'] ?? '').' '.($this->shipping_address['last_name'] ?? ''));

        if ($fromAddress !== '') {
            return $fromAddress;
        }

        $fromUser = trim((string) ($this->user?->name ?? ''));

        return $fromUser === '' ? null : $fromUser;
    }

    /**
     * @param  array<string, mixed>|null  $address
     * @return list<string>
     */
    public function addressLines(?array $address): array
    {
        if ($address === null || $address === []) {
            return [];
        }

        $name = trim(($address['first_name'] ?? '').' '.($address['last_name'] ?? ''));
        $locality = collect([
            $address['city'] ?? null,
            trim(($address['province'] ?? '').' '.($address['postal_code'] ?? '')),
        ])->map(fn (mixed $part): string => trim((string) $part))->filter()->implode(', ');

        return collect([
            $name !== '' ? $name : null,
            $address['company'] ?? null,
            $address['address_line1'] ?? null,
            $address['address_line2'] ?? null,
            $locality !== '' ? $locality : null,
            $address['country_code'] ?? null,
        ])->map(fn (mixed $line): string => trim((string) $line))->filter()->values()->all();
    }

    public function shippingOneLine(): string
    {
        return implode(', ', $this->addressLines($this->shipping_address));
    }

    /**
     * @param  Builder<Order>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || trim($term) === '') {
            return;
        }

        $like = '%'.trim($term).'%';

        $query->where(function (Builder $query) use ($like): void {
            $query->where('order_number', 'like', $like)
                ->orWhere('email', 'like', $like);
        });
    }
}
