<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogController;
use App\Http\Controllers\Admin\CollectionController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\NavigationItemController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\PageController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ShippingMethodController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| The admin panel. EnsureUserIsAdmin keeps customers out of the surface
| entirely; per-model policies still authorize individual actions.
|
*/

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::put('products/order', [ProductController::class, 'reorder'])->name('products.reorder');
        Route::resource('products', ProductController::class)->except('show');
        Route::post('products/{product}/images', [ProductImageController::class, 'store'])->name('products.images.store');
        Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
        Route::put('products/{product}/images/order', [ProductImageController::class, 'reorder'])->name('products.images.reorder');

        Route::resource('collections', CollectionController::class)->except('show');

        Route::resource('coupons', CouponController::class)->except('show');

        Route::get('reviews', [ReviewController::class, 'index'])->name('reviews.index');
        Route::patch('reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');

        Route::get('orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.status');
        Route::post('orders/{order}/refunds', [OrderController::class, 'refund'])->name('orders.refunds.store');

        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

        Route::resource('banners', BannerController::class)->except('show');

        Route::resource('pages', PageController::class)->except('show');

        Route::get('blogs', [BlogController::class, 'index'])->name('blogs.index');
        Route::post('blogs', [BlogController::class, 'store'])->name('blogs.store');
        Route::get('blogs/{blog}/edit', [BlogController::class, 'edit'])->name('blogs.edit');
        Route::put('blogs/{blog}', [BlogController::class, 'update'])->name('blogs.update');
        Route::delete('blogs/{blog}', [BlogController::class, 'destroy'])->name('blogs.destroy');
        Route::post('blog-posts', [BlogController::class, 'storePost'])->name('blog-posts.store');
        Route::put('blog-posts/{post}', [BlogController::class, 'updatePost'])->name('blog-posts.update');
        Route::delete('blog-posts/{post}', [BlogController::class, 'destroyPost'])->name('blog-posts.destroy');

        Route::get('navigation', [NavigationItemController::class, 'index'])->name('navigation.index');
        Route::post('navigation', [NavigationItemController::class, 'store'])->name('navigation.store');
        Route::put('navigation/order', [NavigationItemController::class, 'reorder'])->name('navigation.reorder');
        Route::put('navigation/{navigationItem}', [NavigationItemController::class, 'update'])->name('navigation.update');
        Route::delete('navigation/{navigationItem}', [NavigationItemController::class, 'destroy'])->name('navigation.destroy');

        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
        Route::post('settings/branding', [SettingsController::class, 'updateBranding'])->name('settings.branding.update');

        Route::get('shipping-methods', [ShippingMethodController::class, 'index'])->name('shipping-methods.index');
        Route::post('shipping-methods', [ShippingMethodController::class, 'store'])->name('shipping-methods.store');
        Route::put('shipping-methods/{method}', [ShippingMethodController::class, 'update'])->name('shipping-methods.update');
        Route::delete('shipping-methods/{method}', [ShippingMethodController::class, 'destroy'])->name('shipping-methods.destroy');
    });
