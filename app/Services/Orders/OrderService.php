<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Exceptions\InvalidOrderTransitionException;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusChangedMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderService
{
    public function __construct(private readonly OrderInventory $inventory) {}

    /**
     * @throws InvalidOrderTransitionException
     */
    public function transition(Order $order, OrderStatus $status, ?User $actor = null, ?string $note = null): Order
    {
        DB::transaction(function () use ($order, $status, $actor, $note): void {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $locked->transitionTo($status, $actor, $note);

            if ($status->releasesInventory()) {
                $this->inventory->release($locked);
            }

            $order->setRawAttributes($locked->getAttributes());
            $order->syncOriginal();
        });

        $fresh = $order->fresh(['items']) ?? $order;

        if ($status === OrderStatus::Paid) {
            Mail::to($order->email)->queue(new OrderConfirmationMail($fresh));
        } else {
            Mail::to($order->email)->queue(new OrderStatusChangedMail($fresh));
        }

        return $order->refresh();
    }

    public function updateStaffNote(Order $order, ?string $note): Order
    {
        $order->forceFill(['staff_note' => $note])->save();

        return $order;
    }
}
