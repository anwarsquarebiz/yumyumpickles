<?php

namespace App\Services\Payments;

use App\Services\Settings\SettingsService;

/**
 * The payment choices a customer sees at checkout, and the gateway driver each
 * choice resolves to.
 */
class CheckoutPaymentMethods
{
    public const Razorpay = 'razorpay';

    public const CashOnDelivery = 'cod';

    public function __construct(
        private readonly SettingsService $settings,
        private readonly RazorpaySettings $razorpay,
    ) {}

    public function codEnabled(): bool
    {
        return (bool) $this->settings->get('payments.cod.enabled', true);
    }

    public function setCodEnabled(bool $enabled): void
    {
        $this->settings->set('payments.cod.enabled', $enabled, 'payments');
    }

    /**
     * Cash on delivery is always offered when nothing else is, so checkout can
     * never end up with no way to pay.
     *
     * @return list<array{value: string, label: string, description: string}>
     */
    public function options(): array
    {
        $options = [];

        if ($this->razorpay->isAvailable()) {
            $options[] = [
                'value' => self::Razorpay,
                'label' => 'Pay online',
                'description' => 'UPI, cards, net banking and wallets via Razorpay.',
            ];
        }

        if ($this->codEnabled() || $options === []) {
            $options[] = [
                'value' => self::CashOnDelivery,
                'label' => 'Cash on delivery',
                'description' => 'Pay when your jars arrive.',
            ];
        }

        return $options;
    }

    /**
     * @return list<string>
     */
    public function values(): array
    {
        return array_column($this->options(), 'value');
    }

    /**
     * Gateway driver for a checkout choice. Null means "use the configured
     * default", which keeps older clients and the test suite working.
     */
    public function gatewayFor(?string $method): ?string
    {
        return match ($method) {
            self::Razorpay => 'razorpay',
            self::CashOnDelivery => 'manual',
            default => null,
        };
    }
}
