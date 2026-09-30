<?php

use App\Http\Controllers\Payments\PaymentWebhookController;
use App\Http\Controllers\Payments\RazorpayWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Server-to-server callbacks from the payment gateway. These are exempt from
| CSRF verification (see bootstrap/app.php) and authenticate with a static API
| key instead. Razorpay signs its payload with HMAC, so it has its own route,
| registered before the generic {gateway} route so it wins the match.
|
*/

Route::post('webhooks/payments/razorpay', RazorpayWebhookController::class)
    ->name('webhooks.payments.razorpay');

Route::post('webhooks/payments/{gateway}', PaymentWebhookController::class)
    ->name('webhooks.payments');
