<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/storefront.php';
require __DIR__.'/admin.php';
require __DIR__.'/webhooks.php';
require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
