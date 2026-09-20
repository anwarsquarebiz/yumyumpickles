<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Processing => 'Processing',
            self::Shipped => 'Shipped',
            self::Delivered => 'Delivered',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
        };
    }

    /**
     * Statuses this status is allowed to move to.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Paid, self::Cancelled],
            self::Paid => [self::Processing, self::Cancelled, self::Refunded],
            self::Processing => [self::Shipped, self::Cancelled, self::Refunded],
            self::Shipped => [self::Delivered, self::Refunded],
            self::Delivered => [self::Refunded],
            self::Cancelled, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), strict: true);
    }

    /**
     * Terminal statuses can never transition again.
     */
    public function isFinal(): bool
    {
        return $this->allowedTransitions() === [];
    }

    /**
     * Whether the order has been paid for at some point in its life.
     */
    public function isPaid(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Processing,
            self::Shipped,
            self::Delivered,
            self::Refunded,
        ], strict: true);
    }

    /**
     * Whether reserved inventory should be released back to stock.
     */
    public function releasesInventory(): bool
    {
        return in_array($this, [self::Cancelled, self::Refunded], strict: true);
    }

    public function isRefundable(): bool
    {
        return $this->canTransitionTo(self::Refunded);
    }

    /**
     * Tailwind-friendly badge tone used by the admin and account UIs.
     */
    public function tone(): string
    {
        return match ($this) {
            self::Pending => 'amber',
            self::Paid => 'emerald',
            self::Processing => 'blue',
            self::Shipped => 'indigo',
            self::Delivered => 'green',
            self::Cancelled => 'zinc',
            self::Refunded => 'rose',
        };
    }

    /**
     * @return array<int, array{value: string, label: string, tone: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'tone' => $status->tone(),
            ],
            self::cases(),
        );
    }
}
