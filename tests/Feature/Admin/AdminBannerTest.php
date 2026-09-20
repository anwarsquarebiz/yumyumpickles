<?php

use App\Enums\PublishStatus;
use App\Models\Banner;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    Storage::fake('public');
});

/**
 * @return array<string, mixed>
 */
function bannerPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Summer wellness',
        'subtitle' => 'Vitamins delivered to your door',
        'button_label' => 'Shop now',
        'button_url' => '/products',
        'alt' => 'Bottles of vitamins',
        'position' => 1,
        'status' => PublishStatus::Published->value,
        'image' => UploadedFile::fake()->image('hero.jpg', 1600, 600),
    ], $overrides);
}

it('lists banners for staff', function () {
    Banner::factory()->count(2)->create();

    $this->actingAs($this->admin)
        ->get('/admin/banners')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/banners/index')
            ->has('banners.data', 2)
        );
});

it('creates a published banner with an image', function () {
    $this->actingAs($this->admin)
        ->post('/admin/banners', bannerPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $banner = Banner::query()->firstOrFail();

    expect($banner->title)->toBe('Summer wellness')
        ->and($banner->isPublished())->toBeTrue()
        ->and($banner->image_path)->not->toBeNull();

    Storage::disk('public')->assertExists($banner->image_path);
});

it('requires an image when creating', function () {
    $this->actingAs($this->admin)
        ->post('/admin/banners', bannerPayload(['image' => null]))
        ->assertSessionHasErrors('image');
});

it('rejects a non image upload', function () {
    $this->actingAs($this->admin)
        ->post('/admin/banners', bannerPayload([
            'image' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ]))
        ->assertSessionHasErrors('image');
});

it('leaves a draft unpublished', function () {
    $this->actingAs($this->admin)
        ->post('/admin/banners', bannerPayload(['status' => PublishStatus::Draft->value]));

    expect(Banner::query()->firstOrFail()->published_at)->toBeNull();
});

it('updates a banner', function () {
    $banner = Banner::factory()->create(['title' => 'Old']);

    $this->actingAs($this->admin)
        ->from("/admin/banners/{$banner->id}/edit")
        ->put("/admin/banners/{$banner->id}", bannerPayload([
            'title' => 'Renamed',
            'image' => null,
        ]))
        ->assertRedirect("/admin/banners/{$banner->id}/edit");

    expect($banner->refresh()->title)->toBe('Renamed');
});

it('deletes a banner and its image', function () {
    $banner = Banner::factory()->create([
        'image_disk' => 'public',
        'image_path' => 'banners/old.jpg',
    ]);
    Storage::disk('public')->put('banners/old.jpg', 'x');

    $this->actingAs($this->admin)
        ->delete("/admin/banners/{$banner->id}")
        ->assertRedirect('/admin/banners');

    expect(Banner::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing('banners/old.jpg');
});

it('forbids customers from managing banners', function () {
    $this->actingAs(User::factory()->customer()->create())
        ->get('/admin/banners')
        ->assertForbidden();
});
