<?php

use App\Support\Money;

it('builds from minor units', function () {
    expect(Money::fromMinor(1999)->amount)->toBe(1999);
});

it('builds from a decimal string without float drift', function () {
    expect(Money::fromDecimal('19.99')->amount)->toBe(1999)
        ->and(Money::fromDecimal('0.01')->amount)->toBe(1)
        ->and(Money::fromDecimal('1234.56')->amount)->toBe(123456);
});

it('round trips through a decimal string', function () {
    expect(Money::fromMinor(1999)->toDecimal())->toBe('19.99')
        ->and(Money::fromMinor(0)->toDecimal())->toBe('0.00')
        ->and(Money::fromMinor(5)->toDecimal())->toBe('0.05');
});

it('adds and subtracts', function () {
    $total = Money::fromMinor(1000)->plus(Money::fromMinor(550));

    expect($total->amount)->toBe(1550)
        ->and($total->minus(Money::fromMinor(50))->amount)->toBe(1500);
});

it('multiplies for line totals', function () {
    expect(Money::fromMinor(1999)->multipliedBy(3)->amount)->toBe(5997);
});

it('calculates a percentage from basis points', function () {
    // 10% of 19.99 rounds to 2.00
    expect(Money::fromMinor(1999)->percentage(1000)->amount)->toBe(200)
        ->and(Money::fromMinor(10000)->percentage(2500)->amount)->toBe(2500)
        ->and(Money::fromMinor(10000)->percentage(10000)->amount)->toBe(10000);
});

it('clamps a negative amount to zero', function () {
    expect(Money::fromMinor(500)->minus(Money::fromMinor(800))->atLeastZero()->amount)->toBe(0);
});

it('caps an amount at a ceiling so a discount cannot exceed the subtotal', function () {
    $discount = Money::fromMinor(5000);
    $subtotal = Money::fromMinor(3000);

    expect($discount->cappedAt($subtotal)->amount)->toBe(3000)
        ->and(Money::fromMinor(1000)->cappedAt($subtotal)->amount)->toBe(1000);
});

it('sums a list and returns zero for an empty list', function () {
    expect(Money::sum(Money::fromMinor(100), Money::fromMinor(250), Money::fromMinor(5))->amount)->toBe(355)
        ->and(Money::sum()->amount)->toBe(0);
});

it('compares amounts', function () {
    $ten = Money::fromMinor(1000);
    $twenty = Money::fromMinor(2000);

    expect($twenty->greaterThan($ten))->toBeTrue()
        ->and($ten->lessThan($twenty))->toBeTrue()
        ->and($ten->greaterThanOrEqualTo(Money::fromMinor(1000)))->toBeTrue()
        ->and($ten->equals(Money::fromMinor(1000)))->toBeTrue()
        ->and($ten->isZero())->toBeFalse()
        ->and(Money::zero()->isZero())->toBeTrue();
});

it('refuses to combine different currencies', function () {
    Money::fromMinor(100, 'USD')->plus(Money::fromMinor(100, 'EUR'));
})->throws(InvalidArgumentException::class);

it('rejects a non numeric decimal', function () {
    Money::fromDecimal('not-money');
})->throws(InvalidArgumentException::class);

it('serialises for the frontend', function () {
    expect(Money::fromMinor(1999, 'USD')->toArray())->toBe([
        'amount' => 1999,
        'currency' => 'USD',
        'formatted' => '$19.99',
        'decimal' => '19.99',
    ]);
});

it('formats thousands with separators', function () {
    expect(Money::fromMinor(123456789)->format())->toBe('$1,234,567.89');
});

it('is immutable', function () {
    $original = Money::fromMinor(1000);
    $original->plus(Money::fromMinor(500));

    expect($original->amount)->toBe(1000);
});
