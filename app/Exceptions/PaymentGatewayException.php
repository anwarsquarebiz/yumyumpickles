<?php

namespace App\Exceptions;

use RuntimeException;

class PaymentGatewayException extends RuntimeException
{
    public static function configuration(string $message): self
    {
        return new self($message);
    }

    public static function requestFailed(string $message): self
    {
        return new self("Payment gateway request failed: {$message}");
    }

    public static function unexpectedResponse(): self
    {
        return new self('The payment gateway returned an unexpected response.');
    }
}
