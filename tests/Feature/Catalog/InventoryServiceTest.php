<?php

use App\Enums\InventoryMovementReason;
use App\Exceptions\InsufficientInventoryException;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\Catalog\InventoryService;

beforeEach(function () {
    $this->inventory = app(InventoryService::class);
});

it('decrements stock and records a movement', function () {
    $variant = ProductVariant::factory()->withStock(10)->create();

    $movement = $this->inventory->adjust($variant, -3, InventoryMovementReason::OrderPlaced);

    expect($variant->refresh()->inventory_quantity)->toBe(7)
        ->and($movement->quantity_delta)->toBe(-3)
        ->and($movement->quantity_after)->toBe(7)
        ->and($movement->reason)->toBe(InventoryMovementReason::OrderPlaced);
});

it('keeps the passed instance in step with the new balance', function () {
    $variant = ProductVariant::factory()->withStock(10)->create();

    $this->inventory->adjust($variant, -4, InventoryMovementReason::OrderPlaced);

    expect($variant->inventory_quantity)->toBe(6)
        ->and($variant->isDirty())->toBeFalse();
});

it('increments stock on a restock', function () {
    $variant = ProductVariant::factory()->withStock(5)->create();

    $this->inventory->adjust($variant, 20, InventoryMovementReason::Restock);

    expect($variant->refresh()->inventory_quantity)->toBe(25);
});

it('refuses to oversell a variant that denies backorders', function () {
    $variant = ProductVariant::factory()->withStock(2)->create();

    expect(fn () => $this->inventory->adjust($variant, -3, InventoryMovementReason::OrderPlaced))
        ->toThrow(InsufficientInventoryException::class);

    expect($variant->refresh()->inventory_quantity)->toBe(2)
        ->and(InventoryMovement::query()->count())->toBe(0);
});

it('allows a negative balance when the policy permits backorders', function () {
    $variant = ProductVariant::factory()->backorderable()->create();

    $this->inventory->adjust($variant, -3, InventoryMovementReason::OrderPlaced);

    expect($variant->refresh()->inventory_quantity)->toBe(-3);
});

it('does nothing for a variant that does not track inventory', function () {
    $variant = ProductVariant::factory()->untracked()->create();

    expect($this->inventory->adjust($variant, -5, InventoryMovementReason::OrderPlaced))->toBeNull()
        ->and($variant->refresh()->inventory_quantity)->toBe(0)
        ->and(InventoryMovement::query()->count())->toBe(0);
});

it('links a movement to the record that caused it', function () {
    $variant = ProductVariant::factory()->withStock(10)->create();
    $actor = User::factory()->admin()->create();

    $movement = $this->inventory->adjust(
        variant: $variant,
        delta: -1,
        reason: InventoryMovementReason::ManualAdjustment,
        reference: $actor,
        actor: $actor,
        note: 'Damaged in transit',
    );

    expect($movement->reference_type)->toBe($actor->getMorphClass())
        ->and($movement->reference_id)->toBe($actor->getKey())
        ->and($movement->user_id)->toBe($actor->getKey())
        ->and($movement->note)->toBe('Damaged in transit');
});

it('reserves and releases stock symmetrically', function () {
    $variant = ProductVariant::factory()->withStock(10)->create();

    $this->inventory->reserve($variant, 4);
    expect($variant->refresh()->inventory_quantity)->toBe(6);

    $this->inventory->release($variant, 4);
    expect($variant->refresh()->inventory_quantity)->toBe(10);
});

it('sets an absolute level and records the difference', function () {
    $variant = ProductVariant::factory()->withStock(10)->create();

    $movement = $this->inventory->setLevel($variant, 25);

    expect($variant->refresh()->inventory_quantity)->toBe(25)
        ->and($movement->quantity_delta)->toBe(15)
        ->and($movement->reason)->toBe(InventoryMovementReason::StockCount);
});

it('writes no movement when the level is unchanged', function () {
    $variant = ProductVariant::factory()->withStock(10)->create();

    expect($this->inventory->setLevel($variant, 10))->toBeNull()
        ->and(InventoryMovement::query()->count())->toBe(0);
});

it('asserts a quantity can be fulfilled before committing', function () {
    $variant = ProductVariant::factory()->withStock(3)->create();

    $this->inventory->assertCanFulfill($variant, 3);

    expect(fn () => $this->inventory->assertCanFulfill($variant, 4))
        ->toThrow(InsufficientInventoryException::class);
});

it('reports how much was available on the exception', function () {
    $variant = ProductVariant::factory()->withStock(2)->create();

    try {
        $this->inventory->assertCanFulfill($variant, 5);
        $this->fail('Expected an InsufficientInventoryException.');
    } catch (InsufficientInventoryException $exception) {
        expect($exception->requested)->toBe(5)
            ->and($exception->available)->toBe(2)
            ->and($exception->getMessage())->toContain('Only 2');
    }
});
