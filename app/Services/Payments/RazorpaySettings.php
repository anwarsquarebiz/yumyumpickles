<?php

namespace App\Services\Payments;

use App\Services\Settings\SettingsService;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Merchant-facing Razorpay credentials for both test and live modes.
 *
 * Each mode keeps its own key pair and webhook secret so staff can flip between
 * them without re-entering keys. Secrets are stored encrypted in the settings
 * table and are never returned to Inertia.
 */
class RazorpaySettings
{
    public const Modes = ['test', 'live'];

    public function __construct(private readonly SettingsService $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get('payments.razorpay.enabled', false);
    }

    public function mode(): string
    {
        $mode = (string) $this->settings->get('payments.razorpay.mode', 'test');

        return in_array($mode, self::Modes, true) ? $mode : 'test';
    }

    public function isLive(): bool
    {
        return $this->mode() === 'live';
    }

    public function keyId(?string $mode = null): string
    {
        $mode ??= $this->mode();

        return trim((string) $this->settings->get("payments.razorpay.{$mode}.key_id", ''));
    }

    public function keySecret(?string $mode = null): ?string
    {
        return $this->decrypted('key_secret', $mode ?? $this->mode());
    }

    public function webhookSecret(?string $mode = null): ?string
    {
        return $this->decrypted('webhook_secret', $mode ?? $this->mode());
    }

    /**
     * Every configured webhook secret, so deliveries for payments started in
     * the other mode still verify after staff switch modes.
     *
     * @return list<string>
     */
    public function webhookSecrets(): array
    {
        return array_values(array_filter(array_map(
            fn (string $mode): ?string => $this->webhookSecret($mode),
            self::Modes,
        )));
    }

    public function hasCredentials(?string $mode = null): bool
    {
        return $this->keyId($mode) !== '' && $this->keySecret($mode) !== null;
    }

    /**
     * Whether customers can be offered Razorpay at checkout right now.
     */
    public function isAvailable(): bool
    {
        return $this->enabled() && $this->hasCredentials();
    }

    /**
     * @return array{
     *     enabled: bool,
     *     mode: string,
     *     webhook_url: string,
     *     test: array{key_id: string, has_key_secret: bool, has_webhook_secret: bool},
     *     live: array{key_id: string, has_key_secret: bool, has_webhook_secret: bool}
     * }
     */
    public function adminPayload(): array
    {
        $credentials = fn (string $mode): array => [
            'key_id' => $this->keyId($mode),
            'has_key_secret' => $this->keySecret($mode) !== null,
            'has_webhook_secret' => $this->webhookSecret($mode) !== null,
        ];

        return [
            'enabled' => $this->enabled(),
            'mode' => $this->mode(),
            'webhook_url' => route('webhooks.payments.razorpay'),
            'test' => $credentials('test'),
            'live' => $credentials('live'),
        ];
    }

    /**
     * Blank secrets keep whatever is already stored so staff never have to
     * re-enter them just to change another field.
     *
     * @param  array{
     *     enabled?: mixed,
     *     mode?: mixed,
     *     test?: array{key_id?: mixed, key_secret?: mixed, webhook_secret?: mixed},
     *     live?: array{key_id?: mixed, key_secret?: mixed, webhook_secret?: mixed}
     * }  $payload
     */
    public function update(array $payload): void
    {
        $values = [
            'payments.razorpay.enabled' => (bool) ($payload['enabled'] ?? false),
            'payments.razorpay.mode' => in_array($payload['mode'] ?? null, self::Modes, true) ? $payload['mode'] : 'test',
        ];

        foreach (self::Modes as $mode) {
            $fields = (array) ($payload[$mode] ?? []);

            $values["payments.razorpay.{$mode}.key_id"] = trim((string) ($fields['key_id'] ?? ''));

            foreach (['key_secret', 'webhook_secret'] as $secret) {
                $value = trim((string) ($fields[$secret] ?? ''));

                if ($value !== '') {
                    $values["payments.razorpay.{$mode}.{$secret}"] = Crypt::encryptString($value);
                }
            }
        }

        $this->settings->setMany($values, 'payments');
    }

    private function decrypted(string $field, string $mode): ?string
    {
        $stored = (string) $this->settings->get("payments.razorpay.{$mode}.{$field}", '');

        if ($stored === '') {
            return null;
        }

        try {
            $value = Crypt::decryptString($stored);
        } catch (DecryptException) {
            return null;
        }

        return $value === '' ? null : $value;
    }
}
