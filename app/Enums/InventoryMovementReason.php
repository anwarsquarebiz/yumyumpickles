<?php

namespace App\Enums;

enum InventoryMovementReason: string
{
    case OrderPlaced = 'order_placed';
    case OrderCancelled = 'order_cancelled';
    case OrderRefunded = 'order_refunded';
    case Restock = 'restock';
    case ManualAdjustment = 'manual_adjustment';
    case StockCount = 'stock_count';

    public function label(): string
    {
        return match ($this) {
            self::OrderPlaced => 'Order placed',
            self::OrderCancelled => 'Order cancelled',
            self::OrderRefunded => 'Order refunded',
            self::Restock => 'Restock',
            self::ManualAdjustment => 'Manual adjustment',
            self::StockCount => 'Stock count',
        };
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $reason): array => ['value' => $reason->value, 'label' => $reason->label()],
            self::cases(),
        );
    }
}
