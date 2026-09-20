<?php

namespace App\Jobs;

use App\Services\Ads\MetaConversionsApi;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMetaPurchaseConversion implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [1, 5, 10];

    public int $timeout = 20;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $orderId)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function handle(MetaConversionsApi $conversions): void
    {
        $conversions->sendPurchase($this->orderId);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Meta CAPI purchase failed.', [
            'order_id' => $this->orderId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
