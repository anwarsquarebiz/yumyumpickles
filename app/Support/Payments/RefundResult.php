<?php

namespace App\Support\Payments;

use App\Enums\RefundStatus;

final class RefundResult
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly RefundStatus $status,
        public readonly ?string $reference = null,
        public readonly array $payload = [],
        public readonly ?string $failureReason = null,
    ) {}
}
