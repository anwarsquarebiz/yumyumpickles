<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use RuntimeException;

class InsufficientInventoryException extends RuntimeException
{
    public function __construct(
        public readonly ProductVariant $variant,
        public readonly int $requested,
        public readonly int $available,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function for(ProductVariant $variant, int $requested): self
    {
        return new self(
            variant: $variant,
            requested: $requested,
            available: max(0, $variant->inventory_quantity),
            message: sprintf(
                'Only %d of "%s" %s left in stock, but %d were requested.',
                max(0, $variant->inventory_quantity),
                $variant->displayTitle(),
                max(0, $variant->inventory_quantity) === 1 ? 'is' : 'are',
                $requested,
            ),
        );
    }
}
