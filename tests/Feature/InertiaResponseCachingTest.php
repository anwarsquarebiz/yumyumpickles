<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;

it('forbids browsers from storing inertia json responses', function () {
    $version = app(HandleInertiaRequests::class)->version(Request::create('/'));

    $response = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) $version,
        'X-Requested-With' => 'XMLHttpRequest',
    ])->get('/');

    $response->assertOk()
        ->assertHeader('X-Inertia', 'true')
        ->assertHeader('Vary', 'X-Inertia');

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

it('keeps normal caching headers on full html page loads', function () {
    $response = $this->get('/');

    $response->assertOk()->assertHeader('Vary', 'X-Inertia');

    expect($response->headers->get('Cache-Control'))->not->toContain('no-store');
});
