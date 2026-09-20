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
use Illuminate\Support\Str;

/**
 * Test stand-in. Never talks to a network; the suite drives settlement through
 * the webhook endpoint the same way a real gateway would.
 */
class FakeGateway implements PaymentGateway
{
    public function initiate(Payment $payment): PaymentInitiation
    {
        $reference = 'fake-'.$payment->getKey().'-'.Str::lower(Str::random(8));

        return new PaymentInitiation(
            reference: $reference,
            status: PaymentStatus::Pending,
            redirectUrl: route('checkout.callback', $payment->order),
            payload: ['driver' => 'fake'],
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
            reference: 'fake-refund-'.Str::lower(Str::random(8)),
            payload: [
                'amount' => $amount->amount,
                'reason' => $reason,
            ],
        );
    }
}
