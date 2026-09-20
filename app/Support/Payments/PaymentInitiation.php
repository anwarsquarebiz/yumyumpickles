<?php

namespace App\Support\Payments;

use App\Enums\PaymentStatus;

final class PaymentInitiation
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly ?string $reference,
        public readonly PaymentStatus $status,
        public readonly ?string $redirectUrl = null,
        public readonly array $payload = [],
    ) {}
}
