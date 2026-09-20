<?php

use App\Http\Controllers\Api\AccountApiController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\CartApiController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\CheckoutApiController;
use App\Http\Controllers\Api\FormApiController;
use App\Http\Controllers\Api\ReviewApiController;
use App\Http\Controllers\Api\StorefrontController;
use Illuminate\Support\Facades\Route;

Route::get('/storefront', StorefrontController::class);
Route::get('/products', [CatalogController::class, 'products']);
Route::get('/products/{slug}', [CatalogController::class, 'show']);
Route::get('/collections', [CatalogController::class, 'collections']);
Route::get('/search', [CatalogController::class, 'products']);

Route::get('/cart', [CartApiController::class, 'show']);
Route::post('/cart/items', [CartApiController::class, 'add']);
Route::patch('/cart/items/{item}', [CartApiController::class, 'update']);
Route::delete('/cart/items/{item}', [CartApiController::class, 'destroy']);
Route::post('/cart/coupon', [CartApiController::class, 'applyCoupon']);
Route::delete('/cart/coupon', [CartApiController::class, 'removeCoupon']);

Route::post('/checkout', [CheckoutApiController::class, 'store']);

Route::get('/products/{productId}/reviews', [ReviewApiController::class, 'index']);
Route::post('/reviews', [ReviewApiController::class, 'store']);

Route::post('/contact', [FormApiController::class, 'contact']);
Route::post('/newsletter', [FormApiController::class, 'newsletter']);

Route::post('/register', [AuthApiController::class, 'register']);
Route::post('/login', [AuthApiController::class, 'login']);
Route::post('/logout', [AuthApiController::class, 'logout']);
Route::get('/me', [AuthApiController::class, 'me']);

Route::middleware('auth:sanctum')->group(function () {
    Route::patch('/account', [AccountApiController::class, 'updateProfile']);
    Route::get('/account/orders', [AccountApiController::class, 'orders']);
    Route::get('/account/addresses', [AccountApiController::class, 'addresses']);
    Route::post('/account/addresses', [AccountApiController::class, 'saveAddress']);
    Route::delete('/account/addresses/{address}', [AccountApiController::class, 'deleteAddress']);
    Route::get('/wishlist', [AccountApiController::class, 'wishlist']);
    Route::post('/wishlist', [AccountApiController::class, 'toggleWishlist']);
});
