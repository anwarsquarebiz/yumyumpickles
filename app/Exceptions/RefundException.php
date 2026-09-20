<?php

namespace App\Exceptions;

use RuntimeException;

class RefundException extends RuntimeException
{
    public static function notRefundable(): self
    {
        return new self('This order cannot be refunded.');
    }

    public static function amountTooHigh(): self
    {
        return new self('The refund amount exceeds what remains refundable on this order.');
    }

    public static function noPayment(): self
    {
        return new self('This order has no captured payment to refund.');
    }
}
