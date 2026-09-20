<?php

use App\Services\Currency\CurrencyConverter;
use App\Services\Currency\CurrencyService;
use App\Services\Currency\ExchangeRateService;
use App\Support\Money;

beforeEach(function () {
    app(ExchangeRateService::class)->useRates([
        'USD' => 1,
        'EUR' => 0.5,
        'INR' => 80,
    ]);
});

it('leaves a base-currency amount unchanged', function () {
    $converted = app(CurrencyConverter::class)->convert(Money::fromMinor(1999, 'USD'), 'USD');

    expect($converted->amount)->toBe(1999)
        ->and($converted->currency)->toBe('USD');
});

it('converts from the USD base into a display currency', function () {
    app(CurrencyService::class)->setCurrent('EUR');

    $converted = app(CurrencyConverter::class)->forDisplay(Money::fromMinor(1000, 'USD'));

    expect($converted->amount)->toBe(500)
        ->and($converted->currency)->toBe('EUR')
        ->and($converted->format())->toBe('€5.00');
});

it('converts a display-currency amount back to the USD base', function () {
    $base = app(CurrencyConverter::class)->toBase(Money::fromMinor(8000, 'INR'));

    expect($base->amount)->toBe(100)
        ->and($base->currency)->toBe('USD');
});

it('turns a display-currency price filter into a base decimal', function () {
    app(CurrencyService::class)->setCurrent('EUR');

    expect(app(CurrencyConverter::class)->toBaseDecimal('10.00'))->toBe('20.00');
});
