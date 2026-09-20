<?php

namespace App\Jobs;

use App\Services\Payments\PaymentProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPaymentWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public readonly string $gateway,
        public readonly array $payload,
    ) {}

    public function handle(PaymentProcessor $processor): void
    {
        $processor->applyWebhook($this->gateway, $this->payload);
    }
}
