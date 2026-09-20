<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessPaymentWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway): JsonResponse|Response
    {
        $expected = (string) config('payments.webhook.api_key');
        $header = (string) config('payments.webhook.header', 'X-Api-Key');
        $provided = (string) $request->header($header, '');

        if ($expected === '' || ! hash_equals($expected, $provided)) {
            abort(401, 'Invalid webhook credentials.');
        }

        ProcessPaymentWebhook::dispatch($gateway, $request->all());

        return response()->json(['received' => true]);
    }
}
