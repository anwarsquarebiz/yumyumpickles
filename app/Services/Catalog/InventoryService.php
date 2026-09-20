<?php

namespace App\Services\Catalog;

use App\Enums\InventoryMovementReason;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The single entry point for changing stock levels.
 *
 * Every change goes through a row lock inside a transaction so two concurrent
 * checkouts cannot both pass the availability check and oversell the last unit,
 * and every change writes an inventory_movements row explaining itself.
 */
class InventoryService
{
    /**
     * Apply a relative change to a variant's stock.
     *
     * Returns null when the variant does not track inventory, since there is no
     * balance to move.
     *
     * @throws InsufficientInventoryException when the change would oversell.
     */
    public function adjust(
        ProductVariant $variant,
        int $delta,
        InventoryMovementReason $reason,
        ?Model $reference = null,
        ?User $actor = null,
        ?string $note = null,
    ): ?InventoryMovement {
        if (! $variant->track_inventory) {
            return null;
        }

        return DB::transaction(function () use ($variant, $delta, $reason, $reference, $actor, $note): InventoryMovement {
            $locked = ProductVariant::query()
                ->whereKey($variant->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $quantityAfter = $locked->inventory_quantity + $delta;

            if ($delta < 0 && $quantityAfter < 0 && ! $locked->inventory_policy->allowsOversell()) {
                throw InsufficientInventoryException::for($locked, abs($delta));
            }

            $locked->forceFill(['inventory_quantity' => $quantityAfter])->save();

            /*
             | Keep the caller's instance consistent with what was just written,
             | so it does not report a stale balance.
             */
            $variant->setAttribute('inventory_quantity', $quantityAfter);
            $variant->syncOriginalAttribute('inventory_quantity');

            return InventoryMovement::query()->create([
                'product_variant_id' => $locked->getKey(),
                'quantity_delta' => $delta,
                'quantity_after' => $quantityAfter,
                'reason' => $reason,
                'reference_type' => $reference === null ? null : $reference->getMorphClass(),
                'reference_id' => $reference?->getKey(),
                'user_id' => $actor?->getKey(),
                'note' => $note,
            ]);
        });
    }

    /**
     * Take stock out for a placed order.
     */
    public function reserve(ProductVariant $variant, int $quantity, ?Model $reference = null): ?InventoryMovement
    {
        return $this->adjust(
            variant: $variant,
            delta: -abs($quantity),
            reason: InventoryMovementReason::OrderPlaced,
            reference: $reference,
        );
    }

    /**
     * Put stock back after a cancellation or refund.
     */
    public function release(
        ProductVariant $variant,
        int $quantity,
        InventoryMovementReason $reason = InventoryMovementReason::OrderCancelled,
        ?Model $reference = null,
    ): ?InventoryMovement {
        return $this->adjust(
            variant: $variant,
            delta: abs($quantity),
            reason: $reason,
            reference: $reference,
        );
    }

    /**
     * Set an absolute stock level, as a staff member would after a stock count.
     */
    public function setLevel(
        ProductVariant $variant,
        int $quantity,
        ?User $actor = null,
        ?string $note = null,
        InventoryMovementReason $reason = InventoryMovementReason::StockCount,
    ): ?InventoryMovement {
        if (! $variant->track_inventory) {
            return null;
        }

        $delta = $quantity - $variant->inventory_quantity;

        if ($delta === 0) {
            return null;
        }

        return $this->adjust(
            variant: $variant,
            delta: $delta,
            reason: $reason,
            actor: $actor,
            note: $note,
        );
    }

    /**
     * Guard a quantity before committing to it, for example when adding to cart.
     *
     * @throws InsufficientInventoryException
     */
    public function assertCanFulfill(ProductVariant $variant, int $quantity): void
    {
        if (! $variant->canFulfill($quantity)) {
            throw InsufficientInventoryException::for($variant, $quantity);
        }
    }
}
