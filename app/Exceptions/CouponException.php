<?php

namespace App\Exceptions;

use App\Support\Money;
use RuntimeException;

/**
 * Raised when a discount code cannot be applied. The message is written for the
 * customer, because it is surfaced straight back onto the cart form.
 */
class CouponException extends RuntimeException
{
    public static function notFound(string $code): self
    {
        return new self(sprintf('Discount code "%s" is not valid.', mb_strtoupper($code)));
    }

    public static function inactive(): self
    {
        return new self('This discount code is no longer available.');
    }

    public static function notStarted(): self
    {
        return new self('This discount code is not active yet.');
    }

    public static function expired(): self
    {
        return new self('This discount code has expired.');
    }

    public static function usageLimitReached(): self
    {
        return new self('This discount code has reached its usage limit.');
    }

    public static function customerLimitReached(): self
    {
        return new self('You have already used this discount code.');
    }

    public static function minimumSubtotalNotMet(Money $minimum): self
    {
        return new self(sprintf('Spend at least %s to use this discount code.', $minimum->format()));
    }

    public static function emptyCart(): self
    {
        return new self('Add something to your cart before applying a discount code.');
    }
}
