<?php

use App\Http\Controllers\Payments\PaymentWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webhook Routes
|--------------------------------------------------------------------------
|
| Server-to-server callbacks from the payment gateway. These are exempt from
| CSRF verification (see bootstrap/app.php) and authenticate with a static API
| key instead.
|
*/

Route::post('webhooks/payments/{gateway}', PaymentWebhookController::class)
    ->name('webhooks.payments');
