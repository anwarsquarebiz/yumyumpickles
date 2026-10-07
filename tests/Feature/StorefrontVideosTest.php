<?php

use App\Services\Settings\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('shares home videos with public urls for the video file and poster', function () {
    Storage::fake('public');
    Storage::disk('public')->putFileAs('videos', UploadedFile::fake()->create('vid1.mp4', 10, 'video/mp4'), 'vid1.mp4');

    app(SettingsService::class)->set('storefront.videos', [
        ['title' => 'Prawns Balchao batch', 'tag' => 'Behind The Scenes', 'views' => '24K', 'image' => 'https://cdn.example.com/poster.jpg', 'video' => 'vid1.mp4'],
    ], 'storefront');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('content.videos.0.video', Storage::disk('public')->url('videos/vid1.mp4'))
            ->where('content.videos.0.image', 'https://cdn.example.com/poster.jpg')
        );
});

it('points video filenames at the videos folder even when the file is not on the storage disk', function () {
    Storage::fake('public');

    app(SettingsService::class)->set('storefront.videos', [
        ['title' => 'First taste', 'tag' => 'Customer Reactions', 'views' => '33K', 'video' => 'vid4.mp4'],
    ], 'storefront');

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('content.videos.0.video', Storage::disk('public')->url('videos/vid4.mp4'))
        );
});
