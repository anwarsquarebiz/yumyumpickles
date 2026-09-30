<?php

namespace App\Services\Payments;

use App\Contracts\PaymentGateway;
use App\Services\Payments\Gateways\CustomHttpGateway;
use App\Services\Payments\Gateways\FakeGateway;
use App\Services\Payments\Gateways\ManualGateway;
use App\Services\Payments\Gateways\RazorpayGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function __construct(private readonly RazorpaySettings $razorpay) {}

    public function driver(?string $name = null): PaymentGateway
    {
        $name ??= (string) config('payments.default', 'manual');

        return match ($name) {
            'custom' => new CustomHttpGateway((array) config('payments.gateways.custom', [])),
            'razorpay' => $this->razorpay(),
            'manual' => new ManualGateway,
            'fake' => new FakeGateway,
            default => throw new InvalidArgumentException("Unknown payment gateway [{$name}]."),
        };
    }

    public function razorpay(): RazorpayGateway
    {
        return new RazorpayGateway($this->razorpay, (array) config('payments.gateways.razorpay', []));
    }
}
