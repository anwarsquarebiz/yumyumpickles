<?php

namespace App\Contracts;

use App\Models\Payment;
use App\Support\Money;
use App\Support\Payments\PaymentInitiation;
use App\Support\Payments\PaymentStatusUpdate;
use App\Support\Payments\RefundResult;

interface PaymentGateway
{
    /**
     * Start a payment against the remote gateway (or a local stand-in).
     */
    public function initiate(Payment $payment): PaymentInitiation;

    /**
     * Ask the gateway for the current status of an existing payment.
     */
    public function fetch(Payment $payment): PaymentStatusUpdate;

    /**
     * Refund some or all of a captured payment.
     */
    public function refund(Payment $payment, Money $amount, ?string $reason = null): RefundResult;
}
