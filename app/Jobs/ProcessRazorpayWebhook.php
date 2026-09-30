<?php

namespace App\Jobs;

use App\Services\Payments\RazorpayWebhookProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessRazorpayWebhook implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(public readonly array $payload) {}

    public function handle(RazorpayWebhookProcessor $processor): void
    {
        $processor->handle($this->payload);
    }
}
