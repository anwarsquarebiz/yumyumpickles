<?php

use App\Models\User;
use App\Services\Payments\CheckoutPaymentMethods;
use App\Services\Payments\RazorpaySettings;
use App\Services\Settings\SettingsService;

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function paymentSettingsPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'cod_enabled' => true,
        'razorpay' => [
            'enabled' => false,
            'mode' => 'test',
            'test' => ['key_id' => '', 'key_secret' => '', 'webhook_secret' => ''],
            'live' => ['key_id' => '', 'key_secret' => '', 'webhook_secret' => ''],
        ],
    ], $overrides);
}

it('redirects guests away from payment settings', function () {
    $this->put('/admin/settings/payments', paymentSettingsPayload())->assertRedirect('/login');
});

it('forbids customers from saving payment settings', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->put('/admin/settings/payments', paymentSettingsPayload())
        ->assertForbidden();
});

it('lets staff save encrypted razorpay keys for both modes', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings/payments', paymentSettingsPayload([
            'razorpay' => [
                'enabled' => true,
                'mode' => 'live',
                'test' => ['key_id' => 'rzp_test_abc123', 'key_secret' => 'test-secret', 'webhook_secret' => 'test-hook'],
                'live' => ['key_id' => 'rzp_live_xyz789', 'key_secret' => 'live-secret', 'webhook_secret' => 'live-hook'],
            ],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHas('success', 'Payment settings saved.');

    $razorpay = app(RazorpaySettings::class);

    expect($razorpay->enabled())->toBeTrue()
        ->and($razorpay->mode())->toBe('live')
        ->and($razorpay->keyId())->toBe('rzp_live_xyz789')
        ->and($razorpay->keySecret())->toBe('live-secret')
        ->and($razorpay->keySecret('test'))->toBe('test-secret')
        ->and($razorpay->webhookSecrets())->toBe(['test-hook', 'live-hook'])
        ->and(app(SettingsService::class)->get('payments.razorpay.live.key_secret'))->not->toBe('live-secret')
        ->and($razorpay->isAvailable())->toBeTrue();
});

it('toggles between test and live mode without re-entering secrets', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->put('/admin/settings/payments', paymentSettingsPayload([
        'razorpay' => [
            'enabled' => true,
            'test' => ['key_id' => 'rzp_test_abc123', 'key_secret' => 'test-secret'],
            'live' => ['key_id' => 'rzp_live_xyz789', 'key_secret' => 'live-secret'],
        ],
    ]))->assertSessionHasNoErrors();

    $this->actingAs($admin)->put('/admin/settings/payments', paymentSettingsPayload([
        'razorpay' => [
            'enabled' => true,
            'mode' => 'live',
            'test' => ['key_id' => 'rzp_test_abc123'],
            'live' => ['key_id' => 'rzp_live_xyz789'],
        ],
    ]))->assertSessionHasNoErrors();

    $razorpay = app(RazorpaySettings::class);

    expect($razorpay->mode())->toBe('live')
        ->and($razorpay->keySecret('live'))->toBe('live-secret')
        ->and($razorpay->keySecret('test'))->toBe('test-secret');
});

it('never sends razorpay secrets back to the admin page', function () {
    app(RazorpaySettings::class)->update([
        'enabled' => true,
        'mode' => 'test',
        'test' => ['key_id' => 'rzp_test_abc123', 'key_secret' => 'super-secret-value', 'webhook_secret' => 'hook-secret-value'],
    ]);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/settings')
        ->assertOk()
        ->assertInertia(function ($page): void {
            $props = $page->toArray()['props'];

            expect(json_encode($props))->not->toContain('super-secret-value')
                ->and(json_encode($props))->not->toContain('hook-secret-value')
                ->and(collect($props['settings'])->keys()->filter(fn (string $key): bool => str_starts_with($key, 'payments.')))->toBeEmpty()
                ->and($props['payments']['razorpay']['test'])->toBe([
                    'key_id' => 'rzp_test_abc123',
                    'has_key_secret' => true,
                    'has_webhook_secret' => true,
                ])
                ->and($props['payments']['razorpay']['webhook_url'])->toEndWith('/webhooks/payments/razorpay')
                ->and($props['payments']['cod_enabled'])->toBeTrue();
        });
});

it('refuses to enable razorpay without keys for the selected mode', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from('/admin/settings')
        ->put('/admin/settings/payments', paymentSettingsPayload([
            'razorpay' => [
                'enabled' => true,
                'mode' => 'live',
                'test' => ['key_id' => 'rzp_test_abc123', 'key_secret' => 'test-secret'],
            ],
        ]))
        ->assertRedirect('/admin/settings')
        ->assertSessionHasErrors(['razorpay.live.key_id', 'razorpay.live.key_secret']);

    expect(app(RazorpaySettings::class)->enabled())->toBeFalse();
});

it('rejects key ids from the wrong mode', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put('/admin/settings/payments', paymentSettingsPayload([
            'razorpay' => [
                'test' => ['key_id' => 'rzp_live_xyz789'],
                'live' => ['key_id' => 'rzp_test_abc123'],
            ],
        ]))
        ->assertSessionHasErrors(['razorpay.test.key_id', 'razorpay.live.key_id']);
});

it('keeps cash on delivery on while razorpay is off', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put('/admin/settings/payments', paymentSettingsPayload(['cod_enabled' => false]))
        ->assertSessionHasErrors('cod_enabled');

    expect(app(CheckoutPaymentMethods::class)->codEnabled())->toBeTrue();
});

it('allows turning cash on delivery off once razorpay is enabled', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put('/admin/settings/payments', paymentSettingsPayload([
            'cod_enabled' => false,
            'razorpay' => [
                'enabled' => true,
                'test' => ['key_id' => 'rzp_test_abc123', 'key_secret' => 'test-secret'],
            ],
        ]))
        ->assertSessionHasNoErrors();

    expect(app(CheckoutPaymentMethods::class)->codEnabled())->toBeFalse()
        ->and(app(CheckoutPaymentMethods::class)->values())->toBe(['razorpay']);
});
