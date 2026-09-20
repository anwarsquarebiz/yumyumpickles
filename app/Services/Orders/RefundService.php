<?php

namespace App\Services\Orders;

use App\Enums\InventoryMovementReason;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Exceptions\RefundException;
use App\Mail\RefundIssuedMail;
use App\Models\Order;
use App\Models\Refund;
use App\Models\User;
use App\Services\Payments\PaymentGatewayManager;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RefundService
{
    public function __construct(
        private readonly PaymentGatewayManager $gateways,
        private readonly OrderInventory $inventory,
    ) {}

    /**
     * @throws RefundException
     */
    public function issue(Order $order, Money $amount, ?User $actor = null, ?string $reason = null, bool $restock = false): Refund
    {
        if (! $order->status->isRefundable() && $order->status !== OrderStatus::Refunded) {
            throw RefundException::notRefundable();
        }

        if ($amount->isZero() || $amount->greaterThan($order->refundableAmount())) {
            throw RefundException::amountTooHigh();
        }

        $payment = $order->payments()
            ->whereIn('status', [PaymentStatus::Paid->value, PaymentStatus::PartiallyRefunded->value])
            ->latest('id')
            ->first();

        if ($payment === null) {
            throw RefundException::noPayment();
        }

        $refund = DB::transaction(function () use ($order, $payment, $amount, $actor, $reason, $restock): Refund {
            $result = $this->gateways->driver($payment->gateway)->refund($payment, $amount, $reason);

            $refund = $order->refunds()->create([
                'payment_id' => $payment->getKey(),
                'user_id' => $actor?->getKey(),
                'amount' => $amount,
                'reason' => $reason,
                'status' => $result->status,
                'gateway_reference' => $result->reference,
                'restock' => $restock,
                'processed_at' => $result->status === RefundStatus::Succeeded ? now() : null,
            ]);

            if ($result->status !== RefundStatus::Succeeded) {
                return $refund;
            }

            $newRefunded = $order->refundedTotal()->plus($amount);

            $order->forceFill(['refunded_amount' => $newRefunded])->save();

            $payment->forceFill([
                'status' => $newRefunded->greaterThanOrEqualTo($order->grandTotal())
                    ? PaymentStatus::Refunded
                    : PaymentStatus::PartiallyRefunded,
            ])->save();

            if ($restock) {
                $this->inventory->release($order, InventoryMovementReason::OrderRefunded);
            }

            if ($newRefunded->greaterThanOrEqualTo($order->grandTotal()) && $order->status->canTransitionTo(OrderStatus::Refunded)) {
                $order->transitionTo(OrderStatus::Refunded, $actor, $reason ?? 'Order refunded.');
            } else {
                $order->recordEvent('refunded', $reason ?? 'Partial refund issued.', $actor);
            }

            return $refund;
        });

        if ($refund->status === RefundStatus::Succeeded) {
            Mail::to($order->email)->queue(new RefundIssuedMail($order->fresh(['items', 'refunds']) ?? $order, $refund));
        }

        return $refund;
    }
}
