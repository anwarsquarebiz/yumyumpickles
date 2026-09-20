<?php

use App\Http\Controllers\Account\AccountController;
use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Storefront\BlogController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CartCouponController;
use App\Http\Controllers\Storefront\CartItemController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\CollectionController;
use App\Http\Controllers\Storefront\ContactController;
use App\Http\Controllers\Storefront\CurrencyController;
use App\Http\Controllers\Storefront\HomeController;
use App\Http\Controllers\Storefront\PageController;
use App\Http\Controllers\Storefront\ProductController;
use App\Http\Requests\Storefront\ContactFormRequest;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Storefront Routes
|--------------------------------------------------------------------------
|
| Customer-facing Laravel + Inertia React shop. Pretty YumYum URLs sit
| alongside the original catalog routes so admin links and CMS pages keep
| working on the same origin.
|
*/

Route::get('/', HomeController::class)->name('home');
Route::post('currency', CurrencyController::class)->name('currency.update');

Route::get('shop', [ProductController::class, 'index'])->name('shop');
Route::get('products', [ProductController::class, 'index'])->name('products.index');
Route::get('product/{slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('collections', [CollectionController::class, 'index'])->name('collections.index');
Route::get('collections/{slug}', [CollectionController::class, 'show'])->name('collections.show');

Route::get('pages/{slug}', [PageController::class, 'show'])->name('pages.show');
Route::post('pages/{slug}/contact', ContactController::class)->middleware('throttle:5,1')->name('pages.contact');

foreach (['our-story', 'faq', 'contact', 'shipping', 'refunds', 'privacy', 'terms'] as $pageSlug) {
    Route::get($pageSlug, fn () => app(PageController::class)->show($pageSlug))->name(str_replace('-', '_', $pageSlug));
}

Route::post('contact', function (ContactFormRequest $request) {
    return app(ContactController::class)($request, 'contact');
})->middleware('throttle:5,1')->name('contact.submit');

Route::get('recipes', fn () => app(BlogController::class)->show('recipes'))->name('recipes');
Route::get('recipes/{postSlug}', fn (string $postSlug) => app(BlogController::class)->post('recipes', $postSlug))->name('recipes.show');
Route::get('blogs/{blogSlug}', [BlogController::class, 'show'])->name('blogs.show');
Route::get('blogs/{blogSlug}/{postSlug}', [BlogController::class, 'post'])->name('blogs.posts.show');

Route::get('wishlist', fn () => Inertia::render('storefront/wishlist', [
    'seo' => [
        'title' => 'Wishlist',
        'description' => 'Jars you saved for later from the YumYum pantry.',
    ],
]))->name('wishlist');

Route::get('cart', [CartController::class, 'show'])->name('cart.show');
Route::post('cart/items', [CartItemController::class, 'store'])->name('cart.items.store');
Route::patch('cart/items/{item}', [CartItemController::class, 'update'])->name('cart.items.update');
Route::delete('cart/items/{item}', [CartItemController::class, 'destroy'])->name('cart.items.destroy');
Route::post('cart/coupon', [CartCouponController::class, 'store'])->name('cart.coupon.store');
Route::delete('cart/coupon', [CartCouponController::class, 'destroy'])->name('cart.coupon.destroy');

Route::get('checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('checkout/{order}/complete', [CheckoutController::class, 'complete'])->name('checkout.complete');
Route::get('checkout/{order}/callback', [CheckoutController::class, 'callback'])->name('checkout.callback');
Route::post('checkout/{order}/callback', [CheckoutController::class, 'callback']);

Route::middleware(['auth'])->prefix('account')->name('account.')->group(function () {
    Route::get('/', [AccountController::class, 'show'])->name('show');
    Route::patch('/', [AccountController::class, 'update'])->name('update');

    Route::get('orders', [AccountOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');

    Route::get('addresses', [AddressController::class, 'index'])->name('addresses.index');
    Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::put('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
});
