<?php

namespace App\Services\Payments;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Jobs\SendMetaPurchaseConversion;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Orders\OrderInventory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Applies inbound payment status changes.
 *
 * The webhook is the source of truth. Repeated deliveries for a settled
 * payment are ignored so inventory and coupons cannot be touched twice.
 */
class PaymentProcessor
{
    public function __construct(private readonly OrderInventory $inventory) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function applyWebhook(string $gateway, array $payload): ?Payment
    {
        $map = config('payments.webhook.payload_map', []);
        $reference = Arr::get($payload, $map['reference'] ?? 'id');
        $remoteStatus = strtolower((string) Arr::get($payload, $map['status'] ?? 'status', ''));

        if (! is_string($reference) || $reference === '') {
            return null;
        }

        $status = $this->mapStatus($gateway, $remoteStatus);

        return $this->settle($gateway, $reference, $status, $payload);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function settle(string $gateway, string $reference, PaymentStatus $status, array $payload = []): ?Payment
    {
        return DB::transaction(function () use ($gateway, $reference, $status, $payload): ?Payment {
            $payment = Payment::query()
                ->where('gateway', $gateway)
                ->where('gateway_reference', $reference)
                ->lockForUpdate()
                ->first();

            if ($payment === null) {
                return null;
            }

            if ($payment->status->isSettled() && $payment->status === $status) {
                return $payment;
            }

            if ($payment->status === PaymentStatus::Paid && $status === PaymentStatus::Paid) {
                return $payment;
            }

            $payment->forceFill([
                'status' => $status,
                'response_payload' => $payload,
                'paid_at' => $status === PaymentStatus::Paid ? now() : $payment->paid_at,
                'failure_reason' => $status === PaymentStatus::Failed
                    ? (string) ($payload['failure_reason'] ?? $payload['message'] ?? 'Payment failed')
                    : $payment->failure_reason,
            ])->save();

            $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            if ($status === PaymentStatus::Paid && $order->status === OrderStatus::Pending) {
                $order->transitionTo(OrderStatus::Paid, note: 'Payment captured.');
                Mail::to($order->email)->queue(new OrderConfirmationMail($order->load('items')));
                SendMetaPurchaseConversion::dispatch($order->id);
            }

            if (in_array($status, [PaymentStatus::Failed, PaymentStatus::Cancelled], true) && $order->status === OrderStatus::Pending) {
                $order->transitionTo(OrderStatus::Cancelled, note: 'Payment '.$status->value.'.');
                $this->inventory->release($order);
            }

            return $payment->fresh('order');
        });
    }

    private function mapStatus(string $gateway, string $remote): PaymentStatus
    {
        $map = config("payments.gateways.{$gateway}.status_map", config('payments.gateways.custom.status_map', []));
        $canonical = $map[$remote] ?? $remote;

        return PaymentStatus::tryFrom($canonical) ?? PaymentStatus::Pending;
    }
}
