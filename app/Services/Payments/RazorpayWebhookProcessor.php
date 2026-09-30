<?php

namespace App\Services\Payments;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Support\Arr;

/**
 * Turns verified Razorpay webhook events into payment settlements.
 *
 * Only capture events settle a payment. A failed attempt is ignored because the
 * customer can retry inside the same Razorpay order; cancelling on the first
 * decline would release their stock mid-checkout.
 */
class RazorpayWebhookProcessor
{
    public const SettlingEvents = ['payment.captured', 'order.paid'];

    public function __construct(private readonly PaymentProcessor $processor) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): ?Payment
    {
        if (! in_array($payload['event'] ?? null, self::SettlingEvents, true)) {
            return null;
        }

        $capture = Arr::get($payload, 'payload.payment.entity', []);
        $orderId = Arr::get($capture, 'order_id') ?? Arr::get($payload, 'payload.order.entity.id');

        if (! is_string($orderId) || $orderId === '' || Arr::get($capture, 'status') !== 'captured') {
            return null;
        }

        $payment = Payment::query()
            ->where('gateway', 'razorpay')
            ->where('gateway_reference', $orderId)
            ->first();

        if ($payment === null || ! $this->matchesAmount($payment, $capture)) {
            return null;
        }

        return $this->processor->settle('razorpay', $orderId, PaymentStatus::Paid, $payload);
    }

    /**
     * @param  array<string, mixed>  $capture
     */
    private function matchesAmount(Payment $payment, array $capture): bool
    {
        return (int) ($capture['amount'] ?? -1) === $payment->money()->amount
            && strtoupper((string) ($capture['currency'] ?? '')) === strtoupper($payment->currency);
    }
}
