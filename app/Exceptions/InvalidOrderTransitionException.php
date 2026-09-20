<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use RuntimeException;

class InvalidOrderTransitionException extends RuntimeException
{
    public static function from(OrderStatus $from, OrderStatus $to): self
    {
        return new self(sprintf('Cannot move an order from %s to %s.', $from->label(), $to->label()));
    }
}
