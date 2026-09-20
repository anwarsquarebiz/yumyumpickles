<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paid = 'paid';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Authorized => 'Authorized',
            self::Paid => 'Paid',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially refunded',
        };
    }

    /**
     * Settled payments must never be re-processed by a repeated webhook.
     */
    public function isSettled(): bool
    {
        return in_array($this, [
            self::Paid,
            self::Failed,
            self::Cancelled,
            self::Refunded,
            self::PartiallyRefunded,
        ], strict: true);
    }

    public function isSuccessful(): bool
    {
        return in_array($this, [self::Paid, self::Refunded, self::PartiallyRefunded], strict: true);
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $status): array => ['value' => $status->value, 'label' => $status->label()],
            self::cases(),
        );
    }
}
