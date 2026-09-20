<?php

namespace App\Services\Payments\Gateways;

use App\Contracts\PaymentGateway;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Models\Payment;
use App\Support\Money;
use App\Support\Payments\PaymentInitiation;
use App\Support\Payments\PaymentStatusUpdate;
use App\Support\Payments\RefundResult;

/**
 * Offline settlement. The order stays pending until staff mark it paid.
 */
class ManualGateway implements PaymentGateway
{
    public function initiate(Payment $payment): PaymentInitiation
    {
        return new PaymentInitiation(
            reference: 'manual-'.$payment->getKey(),
            status: PaymentStatus::Pending,
            redirectUrl: route('checkout.complete', $payment->order),
            payload: ['driver' => 'manual'],
        );
    }

    public function fetch(Payment $payment): PaymentStatusUpdate
    {
        return new PaymentStatusUpdate(
            status: $payment->status,
            reference: $payment->gateway_reference,
        );
    }

    public function refund(Payment $payment, Money $amount, ?string $reason = null): RefundResult
    {
        return new RefundResult(
            status: RefundStatus::Succeeded,
            reference: 'manual-refund-'.$payment->getKey(),
            payload: [
                'amount' => $amount->amount,
                'reason' => $reason,
            ],
        );
    }
}
