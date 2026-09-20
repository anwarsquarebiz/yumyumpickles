<?php

namespace App\Exceptions;

use RuntimeException;

class CheckoutException extends RuntimeException
{
    public static function emptyCart(): self
    {
        return new self('Your cart is empty.');
    }

    public static function unavailableItems(): self
    {
        return new self('Some items in your cart are no longer available. Review your cart and try again.');
    }

    public static function guestCheckoutDisabled(): self
    {
        return new self('Please sign in to complete your purchase.');
    }

    public static function shippingUnavailable(): self
    {
        return new self('No shipping methods are available for this order.');
    }

    public static function paymentFailed(string $reason): self
    {
        return new self($reason);
    }
}
