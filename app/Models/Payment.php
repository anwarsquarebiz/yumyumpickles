<?php

namespace App\Models;

use App\Casts\MoneyCast;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gateway',
        'gateway_reference',
        'status',
        'amount',
        'currency',
        'redirect_url',
        'request_payload',
        'response_payload',
        'failure_reason',
        'paid_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount' => MoneyCast::class,
            'request_payload' => 'array',
            'response_payload' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return HasMany<Refund, $this>
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function money(): Money
    {
        return $this->amount ?? Money::zero($this->currency);
    }
}
