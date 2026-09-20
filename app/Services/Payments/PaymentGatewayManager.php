<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Services\Payments\Gateways\CustomHttpGateway;
use App\Services\Payments\Gateways\FakeGateway;
use App\Services\Payments\Gateways\ManualGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= (string) config('payments.default', 'manual');

        return match ($name) {
            'custom' => new CustomHttpGateway((array) config('payments.gateways.custom', [])),
            'manual' => new ManualGateway,
            'fake' => new FakeGateway,
            default => throw new InvalidArgumentException("Unknown payment gateway [{$name}]."),
        };
    }
}
