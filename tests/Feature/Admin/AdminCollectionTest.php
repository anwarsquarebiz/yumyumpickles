<?php

use App\Enums\PublishStatus;
use App\Models\Collection;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

/**
 * @return array<string, mixed>
 */
function collectionPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Cold and Flu',
        'slug' => '',
        'description' => 'Everything for the season',
        'status' => PublishStatus::Published->value,
        'seo_title' => '',
        'seo_description' => '',
        'position' => 0,
        'product_ids' => [],
    ], $overrides);
}

it('lists collections for staff', function () {
    Collection::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->get('/admin/collections')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/collections/index')
            ->has('collections.data', 2)
        );
});

it('creates a collection with a generated slug', function () {
    $this->actingAs($this->admin)
        ->post('/admin/collections', collectionPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $collection = Collection::query()->firstOrFail();

    expect($collection->slug)->toBe('cold-and-flu')
        ->and($collection->isPublished())->toBeTrue();
});

it('leaves a draft collection unpublished', function () {
    $this->actingAs($this->admin)
        ->post('/admin/collections', collectionPayload(['status' => PublishStatus::Draft->value]));

    expect(Collection::query()->firstOrFail()->published_at)->toBeNull();
});

it('requires a title', function () {
    $this->actingAs($this->admin)
        ->post('/admin/collections', collectionPayload(['title' => '']))
        ->assertSessionHasErrors('title');
});

it('attaches products in the submitted order', function () {
    $first = Product::factory()->create();
    $second = Product::factory()->create();

    $this->actingAs($this->admin)
        ->post('/admin/collections', collectionPayload(['product_ids' => [$second->id, $first->id]]));

    $collection = Collection::query()->firstOrFail();

    expect($collection->products()->pluck('products.id')->all())->toBe([$second->id, $first->id]);
});

it('stores an uploaded image', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post('/admin/collections', collectionPayload([
            'image' => UploadedFile::fake()->image('range.jpg'),
        ]));

    $collection = Collection::query()->firstOrFail();

    expect($collection->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($collection->image_path);
});

it('rejects a non image upload', function () {
    Storage::fake('public');

    $this->actingAs($this->admin)
        ->post('/admin/collections', collectionPayload([
            'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('image');
});

it('updates a collection', function () {
    $collection = Collection::factory()->create(['title' => 'Old']);

    $this->actingAs($this->admin)
        ->from("/admin/collections/{$collection->id}/edit")
        ->put("/admin/collections/{$collection->id}", collectionPayload(['title' => 'Renamed']))
        ->assertRedirect("/admin/collections/{$collection->id}/edit");

    expect($collection->refresh()->title)->toBe('Renamed');
});

it('removes the image when asked', function () {
    Storage::fake('public');

    $collection = Collection::factory()->create([
        'image_disk' => 'public',
        'image_path' => 'collections/old.jpg',
    ]);
    Storage::disk('public')->put('collections/old.jpg', 'x');

    $this->actingAs($this->admin)
        ->put("/admin/collections/{$collection->id}", collectionPayload(['remove_image' => true]));

    expect($collection->refresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing('collections/old.jpg');
});

it('deletes a collection without deleting its products', function () {
    $collection = Collection::factory()->create();
    $product = Product::factory()->create();
    $collection->products()->attach($product);

    $this->actingAs($this->admin)
        ->delete("/admin/collections/{$collection->id}")
        ->assertRedirect('/admin/collections');

    expect(Collection::query()->count())->toBe(0)
        ->and(Product::query()->count())->toBe(1);
});

it('forbids a customer from managing collections', function () {
    $collection = Collection::factory()->create();
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)->get('/admin/collections')->assertForbidden();
    $this->actingAs($customer)->post('/admin/collections', collectionPayload())->assertForbidden();
    $this->actingAs($customer)->get("/admin/collections/{$collection->id}/edit")->assertForbidden();
    $this->actingAs($customer)->delete("/admin/collections/{$collection->id}")->assertForbidden();
});
