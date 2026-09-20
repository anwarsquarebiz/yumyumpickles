<?php

namespace App\Exceptions;

use App\Models\ProductVariant;
use RuntimeException;

/**
 * Raised for cart mutations that are refused for reasons other than stock, with
 * customer-facing messages.
 */
class CartException extends RuntimeException
{
    public static function unavailableVariant(ProductVariant $variant): self
    {
        return new self(sprintf('"%s" is not available for purchase.', $variant->displayTitle()));
    }

    public static function quantityTooHigh(int $maximum): self
    {
        return new self(sprintf('You can order at most %d of this item.', $maximum));
    }

    public static function emptyCart(): self
    {
        return new self('Your cart is empty.');
    }
}
