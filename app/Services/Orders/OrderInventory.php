<?php

namespace App\Services\Orders;

use App\Enums\InventoryMovementReason;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Catalog\InventoryService;

/**
 * Releases reserved stock when an order is cancelled or fully refunded.
 *
 * Idempotent: a second call is a no-op because we only release when the
 * variant still exists and the movement reason has not already been recorded
 * against the order item.
 */
class OrderInventory
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function release(Order $order, InventoryMovementReason $reason = InventoryMovementReason::OrderCancelled): void
    {
        $order->loadMissing('items.variant');

        foreach ($order->items as $item) {
            if ($item->variant === null || $this->alreadyReleased($item, $reason)) {
                continue;
            }

            $this->inventory->release($item->variant, $item->quantity, $reason, $item);
        }
    }

    private function alreadyReleased(OrderItem $item, InventoryMovementReason $reason): bool
    {
        if ($item->variant === null) {
            return true;
        }

        return $item->variant->inventoryMovements()
            ->where('reference_type', $item->getMorphClass())
            ->where('reference_id', $item->getKey())
            ->where('reason', $reason)
            ->exists();
    }
}
