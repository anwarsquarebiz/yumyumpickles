<?php

use App\Enums\InventoryPolicy;
use App\Models\ProductVariant;

/**
 * Stock rules are pure functions of the variant's own attributes, so they are
 * exercised on unsaved models without touching the database.
 */
function variant(array $attributes = []): ProductVariant
{
    return new ProductVariant(array_merge([
        'title' => 'Default',
        'price_amount' => 1000,
        'inventory_quantity' => 5,
        'track_inventory' => true,
        'inventory_policy' => InventoryPolicy::Deny,
    ], $attributes));
}

it('is in stock while quantity remains', function () {
    expect(variant(['inventory_quantity' => 1])->isInStock())->toBeTrue()
        ->and(variant(['inventory_quantity' => 0])->isInStock())->toBeFalse();
});

it('is always in stock when inventory is not tracked', function () {
    expect(variant(['track_inventory' => false, 'inventory_quantity' => 0])->isInStock())->toBeTrue();
});

it('is in stock at zero when backorders are allowed', function () {
    expect(variant(['inventory_quantity' => 0, 'inventory_policy' => InventoryPolicy::Continue])->isInStock())->toBeTrue();
});

it('fulfils only up to the available quantity', function () {
    $subject = variant(['inventory_quantity' => 3]);

    expect($subject->canFulfill(3))->toBeTrue()
        ->and($subject->canFulfill(4))->toBeFalse()
        ->and($subject->canFulfill(0))->toBeFalse()
        ->and($subject->canFulfill(-1))->toBeFalse();
});

it('fulfils any quantity when backorders are allowed', function () {
    expect(variant(['inventory_quantity' => 0, 'inventory_policy' => InventoryPolicy::Continue])->canFulfill(50))->toBeTrue();
});

it('caps the purchasable quantity at the available stock', function () {
    expect(variant(['inventory_quantity' => 4])->purchasableQuantity())->toBe(4)
        ->and(variant(['inventory_quantity' => 0])->purchasableQuantity())->toBe(0);
});

it('caps the purchasable quantity at the per line limit', function () {
    expect(variant(['inventory_quantity' => 5000])->purchasableQuantity())->toBe(99)
        ->and(variant(['track_inventory' => false])->purchasableQuantity())->toBe(99);
});

it('is on sale only when the compare at price is higher', function () {
    expect(variant(['price_amount' => 1000, 'compare_at_price_amount' => 1500])->isOnSale())->toBeTrue()
        ->and(variant(['price_amount' => 1000, 'compare_at_price_amount' => 1000])->isOnSale())->toBeFalse()
        ->and(variant(['price_amount' => 1000])->isOnSale())->toBeFalse();
});

it('flags low stock inside the threshold but not at zero', function () {
    expect(variant(['inventory_quantity' => 3])->isLowStock())->toBeTrue()
        ->and(variant(['inventory_quantity' => 50])->isLowStock())->toBeFalse()
        ->and(variant(['inventory_quantity' => 0])->isLowStock())->toBeFalse();
});

it('builds a display title from its option values', function () {
    expect(variant(['option1' => '500mg', 'option2' => '60 tablets'])->displayTitle())->toBe('500mg / 60 tablets')
        ->and(variant(['title' => 'Default'])->displayTitle())->toBe('Default');
});

it('lists only the option values that are set', function () {
    expect(variant(['option1' => 'A', 'option3' => 'C'])->optionValues())->toBe(['A', 'C']);
});
