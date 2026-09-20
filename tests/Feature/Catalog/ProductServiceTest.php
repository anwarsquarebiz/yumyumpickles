<?php

use App\Enums\InventoryPolicy;
use App\Enums\ProductStatus;
use App\Models\Collection;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Services\Catalog\ProductService;

beforeEach(function () {
    $this->service = app(ProductService::class);
});

/**
 * @return array<string, mixed>
 */
function productPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Paracetamol 500mg',
        'status' => ProductStatus::Active->value,
        'description' => 'Pain relief',
        'published_at' => now()->toDateTimeString(),
        'variants' => [
            [
                'title' => 'Pack of 16',
                'sku' => 'PARA-16',
                'price' => '4.99',
                'inventory_quantity' => 40,
                'inventory_policy' => InventoryPolicy::Deny->value,
            ],
        ],
    ], $overrides);
}

it('creates a product with a generated slug', function () {
    $product = $this->service->create(productPayload());

    expect($product->slug)->toBe('paracetamol-500mg')
        ->and($product->status)->toBe(ProductStatus::Active);
});

it('appends new products to the end of the custom order', function () {
    Product::factory()->create(['position' => 5]);

    $product = $this->service->create(productPayload());

    expect($product->position)->toBe(6);
});

it('reorders products into the requested sequence', function () {
    $first = Product::factory()->create(['position' => 1]);
    $second = Product::factory()->create(['position' => 2]);
    $third = Product::factory()->create(['position' => 3]);

    $this->service->reorder([$third->id, $first->id, $second->id]);

    expect($third->fresh()->position)->toBe(1)
        ->and($first->fresh()->position)->toBe(2)
        ->and($second->fresh()->position)->toBe(3);
});

it('appends a suffix when a slug is already taken', function () {
    $this->service->create(productPayload());
    $second = $this->service->create(productPayload([
        'variants' => [['title' => 'Pack of 16', 'sku' => 'PARA-16-B', 'price' => '4.99']],
    ]));

    expect($second->slug)->toBe('paracetamol-500mg-2');
});

it('stores prices as integer minor units', function () {
    $product = $this->service->create(productPayload());

    expect($product->variants->first()->price_amount->amount)->toBe(499)
        ->and($product->variants->first()->price_amount->format())->toBe('$4.99');
});

it('records the initial stock as an inventory movement', function () {
    $product = $this->service->create(productPayload());

    $movement = InventoryMovement::query()->firstOrFail();

    expect($product->variants->first()->inventory_quantity)->toBe(40)
        ->and($movement->quantity_delta)->toBe(40)
        ->and($movement->note)->toBe('Initial stock');
});

it('creates a default variant when none are supplied', function () {
    $product = $this->service->create(productPayload(['variants' => []]));

    expect($product->variants)->toHaveCount(1)
        ->and($product->variants->first()->title)->toBe('Default')
        ->and($product->variants->first()->price_amount->amount)->toBe(0);
});

it('creates options alongside variants', function () {
    $product = $this->service->create(productPayload([
        'options' => [
            ['name' => 'Strength', 'position' => 1, 'values' => ['250mg', '500mg']],
        ],
        'variants' => [
            ['title' => '250mg', 'price' => '3.99', 'inventory_quantity' => 5, 'option1' => '250mg', 'inventory_policy' => 'deny'],
            ['title' => '500mg', 'price' => '5.99', 'inventory_quantity' => 8, 'option1' => '500mg', 'inventory_policy' => 'deny'],
        ],
    ]));

    expect($product->options)->toHaveCount(1)
        ->and($product->options->first()->values)->toBe(['250mg', '500mg'])
        ->and($product->variants)->toHaveCount(2)
        ->and($product->variants->pluck('option1')->all())->toBe(['250mg', '500mg']);
});

it('attaches collections in the given order', function () {
    $first = Collection::factory()->create();
    $second = Collection::factory()->create();

    $product = $this->service->create(productPayload([
        'collection_ids' => [$second->id, $first->id],
    ]));

    expect($product->collections->pluck('id')->all())->toBe([$second->id, $first->id]);
});

it('keeps the slug stable when the title changes', function () {
    $product = $this->service->create(productPayload());

    $updated = $this->service->update($product, productPayload(['title' => 'Renamed Product']));

    expect($updated->title)->toBe('Renamed Product')
        ->and($updated->slug)->toBe('paracetamol-500mg');
});

it('changes the slug when one is explicitly supplied', function () {
    $product = $this->service->create(productPayload());

    $updated = $this->service->update($product, productPayload(['slug' => 'new-url']));

    expect($updated->slug)->toBe('new-url');
});

it('updates an existing variant rather than duplicating it', function () {
    $product = $this->service->create(productPayload());
    $variantId = $product->variants->first()->id;

    $updated = $this->service->update($product, productPayload([
        'variants' => [
            ['id' => $variantId, 'title' => 'Pack of 32', 'price' => '8.99', 'inventory_quantity' => 40, 'inventory_policy' => 'deny'],
        ],
    ]));

    expect($updated->variants)->toHaveCount(1)
        ->and($updated->variants->first()->id)->toBe($variantId)
        ->and($updated->variants->first()->title)->toBe('Pack of 32')
        ->and($updated->variants->first()->price_amount->amount)->toBe(899);
});

it('removes variants that were dropped from the payload', function () {
    $product = $this->service->create(productPayload([
        'variants' => [
            ['title' => 'A', 'price' => '1.00', 'inventory_quantity' => 1, 'inventory_policy' => 'deny'],
            ['title' => 'B', 'price' => '2.00', 'inventory_quantity' => 1, 'inventory_policy' => 'deny'],
        ],
    ]));

    $keepId = $product->variants->first()->id;

    $updated = $this->service->update($product, productPayload([
        'variants' => [
            ['id' => $keepId, 'title' => 'A', 'price' => '1.00', 'inventory_quantity' => 1, 'inventory_policy' => 'deny'],
        ],
    ]));

    expect($updated->variants)->toHaveCount(1)
        ->and($updated->variants->first()->id)->toBe($keepId);
});

it('records a movement when stock is edited through the product form', function () {
    $product = $this->service->create(productPayload());
    $variantId = $product->variants->first()->id;

    $this->service->update($product, productPayload([
        'variants' => [
            ['id' => $variantId, 'title' => 'Pack of 16', 'price' => '4.99', 'inventory_quantity' => 55, 'inventory_policy' => 'deny'],
        ],
    ]));

    expect(InventoryMovement::query()->count())->toBe(2)
        ->and(InventoryMovement::query()->latest('id')->first()->quantity_delta)->toBe(15);
});

it('soft deletes a product so order history survives', function () {
    $product = $this->service->create(productPayload());

    $this->service->delete($product);

    expect(Product::query()->count())->toBe(0)
        ->and(Product::query()->withTrashed()->count())->toBe(1);
});

it('does not reuse the slug of a soft deleted product', function () {
    $product = $this->service->create(productPayload());
    $this->service->delete($product);

    $replacement = $this->service->create(productPayload([
        'variants' => [['title' => 'Pack of 16', 'sku' => 'PARA-16-B', 'price' => '4.99']],
    ]));

    expect($replacement->slug)->toBe('paracetamol-500mg-2');
});

it('lets an operator swap a variant row while reusing its sku', function () {
    $product = $this->service->create(productPayload());

    $updated = $this->service->update($product, productPayload([
        'variants' => [['title' => 'Pack of 24', 'sku' => 'PARA-16', 'price' => '6.99']],
    ]));

    expect($updated->variants)->toHaveCount(1)
        ->and($updated->variants->first()->title)->toBe('Pack of 24')
        ->and($updated->variants->first()->sku)->toBe('PARA-16');
});
