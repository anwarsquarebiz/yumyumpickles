<?php

use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('serves files from the public disk', function () {
    Storage::disk('public')->put('products/cenforce.webp', 'webp-bytes');

    $response = $this->get('/storage/products/cenforce.webp');

    $response->assertSuccessful();
    expect($response->streamedContent())->toBe('webp-bytes');
});

it('returns 404 for a missing public disk file', function () {
    $this->get('/storage/products/missing.webp')->assertNotFound();
});

it('does not serve files outside the public disk', function () {
    $this->get('/storage/../private/secret.txt')->assertNotFound();
});
