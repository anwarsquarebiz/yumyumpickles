<?php

use App\Enums\OrderStatus;

it('allows a pending order to be paid or cancelled', function () {
    expect(OrderStatus::Pending->canTransitionTo(OrderStatus::Paid))->toBeTrue()
        ->and(OrderStatus::Pending->canTransitionTo(OrderStatus::Cancelled))->toBeTrue()
        ->and(OrderStatus::Pending->canTransitionTo(OrderStatus::Shipped))->toBeFalse()
        ->and(OrderStatus::Pending->canTransitionTo(OrderStatus::Delivered))->toBeFalse();
});

it('moves a paid order forward through fulfilment', function () {
    expect(OrderStatus::Paid->canTransitionTo(OrderStatus::Processing))->toBeTrue()
        ->and(OrderStatus::Processing->canTransitionTo(OrderStatus::Shipped))->toBeTrue()
        ->and(OrderStatus::Shipped->canTransitionTo(OrderStatus::Delivered))->toBeTrue();
});

it('never moves backwards', function () {
    expect(OrderStatus::Shipped->canTransitionTo(OrderStatus::Paid))->toBeFalse()
        ->and(OrderStatus::Delivered->canTransitionTo(OrderStatus::Shipped))->toBeFalse()
        ->and(OrderStatus::Paid->canTransitionTo(OrderStatus::Pending))->toBeFalse();
});

it('treats cancelled and refunded as final', function () {
    expect(OrderStatus::Cancelled->isFinal())->toBeTrue()
        ->and(OrderStatus::Refunded->isFinal())->toBeTrue()
        ->and(OrderStatus::Cancelled->allowedTransitions())->toBe([])
        ->and(OrderStatus::Delivered->isFinal())->toBeFalse();
});

it('cannot cancel an order that already shipped', function () {
    expect(OrderStatus::Shipped->canTransitionTo(OrderStatus::Cancelled))->toBeFalse()
        ->and(OrderStatus::Delivered->canTransitionTo(OrderStatus::Cancelled))->toBeFalse();
});

it('can refund any order that was paid for', function () {
    expect(OrderStatus::Paid->isRefundable())->toBeTrue()
        ->and(OrderStatus::Processing->isRefundable())->toBeTrue()
        ->and(OrderStatus::Shipped->isRefundable())->toBeTrue()
        ->and(OrderStatus::Delivered->isRefundable())->toBeTrue()
        ->and(OrderStatus::Pending->isRefundable())->toBeFalse()
        ->and(OrderStatus::Cancelled->isRefundable())->toBeFalse();
});

it('knows which statuses mean money was taken', function () {
    expect(OrderStatus::Pending->isPaid())->toBeFalse()
        ->and(OrderStatus::Cancelled->isPaid())->toBeFalse()
        ->and(OrderStatus::Paid->isPaid())->toBeTrue()
        ->and(OrderStatus::Delivered->isPaid())->toBeTrue()
        ->and(OrderStatus::Refunded->isPaid())->toBeTrue();
});

it('releases inventory only when cancelled or refunded', function () {
    expect(OrderStatus::Cancelled->releasesInventory())->toBeTrue()
        ->and(OrderStatus::Refunded->releasesInventory())->toBeTrue()
        ->and(OrderStatus::Shipped->releasesInventory())->toBeFalse();
});

it('exposes the seven statuses the store supports', function () {
    expect(array_map(fn (OrderStatus $status): string => $status->value, OrderStatus::cases()))
        ->toBe(['pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded']);
});

it('gives every status a label and a tone', function () {
    foreach (OrderStatus::cases() as $status) {
        expect($status->label())->not->toBeEmpty()
            ->and($status->tone())->not->toBeEmpty();
    }
});
