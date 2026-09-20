<?php

namespace App\Support\Payments;

use App\Enums\PaymentStatus;

final class PaymentStatusUpdate
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly PaymentStatus $status,
        public readonly ?string $reference = null,
        public readonly array $payload = [],
        public readonly ?string $failureReason = null,
    ) {}
}
