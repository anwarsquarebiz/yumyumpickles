<?php

use App\Services\Currency\ExchangeRateService;
use Illuminate\Support\Facades\Http;

it('returns fallback rates when fetching is disabled', function () {
    config(['shop.currency.fetch_rates' => false]);

    $rates = app(ExchangeRateService::class)->rates();

    expect($rates['USD'])->toBe(1.0)
        ->and($rates)->toHaveKey('EUR')
        ->and($rates['EUR'])->toBeGreaterThan(0);
});

it('fetches and caches live rates against USD', function () {
    config(['shop.currency.fetch_rates' => true]);

    Http::preventStrayRequests();
    Http::fake([
        'open.er-api.com/*' => Http::response([
            'result' => 'success',
            'rates' => [
                'USD' => 1,
                'EUR' => 0.91,
                'GBP' => 0.78,
            ],
        ]),
    ]);

    $service = app(ExchangeRateService::class);

    expect($service->rate('EUR'))->toBe(0.91)
        ->and($service->rate('USD'))->toBe(1.0);

    Http::assertSentCount(1);

    expect($service->rate('GBP'))->toBe(0.78);
    Http::assertSentCount(1);
});

it('falls back when the rates endpoint fails', function () {
    config(['shop.currency.fetch_rates' => true]);

    Http::preventStrayRequests();
    Http::fake([
        'open.er-api.com/*' => Http::failedConnection(),
    ]);

    $rate = app(ExchangeRateService::class)->rate('EUR');

    expect($rate)->toBe((float) config('shop.currency.fallback_rates.EUR'));
});
