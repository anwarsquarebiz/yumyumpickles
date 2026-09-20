<?php

namespace App\Services\Ads;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Server-side Purchase events for Meta's Conversions API.
 *
 * Failures are logged and rethrown only for transient errors so the queue can
 * retry. Checkout and payment settlement never wait on this HTTP call.
 */
class MetaConversionsApi
{
    public function __construct(private readonly MetaAdsSettings $settings) {}

    public function sendPurchase(int $orderId): void
    {
        if (! $this->settings->isConfigured()) {
            return;
        }

        $order = Order::query()->with('items')->find($orderId);

        if ($order === null) {
            return;
        }

        $payload = $this->purchasePayload($order);
        $response = Http::timeout(10)
            ->connectTimeout(3)
            ->acceptJson()
            ->asJson()
            ->post($this->eventsUrl(), $payload);

        if ($response->successful()) {
            return;
        }

        Log::warning('Meta CAPI rejected a purchase event.', [
            'order_id' => $orderId,
            'status' => $response->status(),
        ]);

        if ($response->serverError() || $response->status() === 429) {
            $response->throw();
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function purchasePayload(Order $order): array
    {
        $attribution = is_array($order->ads_attribution) ? $order->ads_attribution : [];
        $eventId = (string) ($attribution['event_id'] ?? Str::uuid());

        $event = [
            'event_name' => 'Purchase',
            'event_time' => now()->timestamp,
            'event_id' => $eventId,
            'event_source_url' => route('checkout.complete', $order),
            'action_source' => 'website',
            'user_data' => $this->userData($order, $attribution),
            'custom_data' => $this->customData($order),
        ];

        $payload = [
            'data' => [$event],
            'access_token' => $this->settings->accessToken(),
        ];

        $testCode = $this->settings->testEventCode();

        if ($testCode !== '') {
            $payload['test_event_code'] = $testCode;
        }

        return $payload;
    }

    private function eventsUrl(): string
    {
        $version = trim((string) config('services.meta.graph_version', 'v21.0'), '/');

        return sprintf('https://graph.facebook.com/%s/%s/events', $version, $this->settings->pixelId());
    }

    /**
     * @param  array<string, mixed>  $attribution
     * @return array<string, mixed>
     */
    private function userData(Order $order, array $attribution): array
    {
        $data = array_filter([
            'fbp' => $attribution['fbp'] ?? null,
            'fbc' => $attribution['fbc'] ?? null,
            'client_ip_address' => $attribution['client_ip'] ?? null,
            'client_user_agent' => $attribution['user_agent'] ?? null,
        ], fn (mixed $value): bool => is_string($value) && $value !== '');

        if ($this->settings->advancedMatching()) {
            $email = MetaUserData::hashEmail($order->email);
            $phone = MetaUserData::hashPhone($order->phone);

            if ($email !== null) {
                $data['em'] = [$email];
            }

            if ($phone !== null) {
                $data['ph'] = [$phone];
            }
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function customData(Order $order): array
    {
        $items = $order->items;
        $contentIds = $items
            ->map(fn (OrderItem $item): string => (string) ($item->product_variant_id ?? $item->product_id ?? $item->sku ?? $item->id))
            ->values()
            ->all();

        $contents = $items->map(function (OrderItem $item): array {
            $id = (string) ($item->product_variant_id ?? $item->product_id ?? $item->sku ?? $item->id);

            return [
                'id' => $id,
                'quantity' => $item->quantity,
                'item_price' => (float) $item->unit_price_amount->toDecimal(),
            ];
        })->values()->all();

        return [
            'currency' => $order->currency,
            'value' => (float) $order->grandTotal()->toDecimal(),
            'content_type' => 'product',
            'content_ids' => $contentIds,
            'contents' => $contents,
            'num_items' => $items->sum('quantity'),
            'order_id' => $order->order_number,
        ];
    }
}
