<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRazorpayWebhook;
use App\Services\Payments\RazorpaySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RazorpayWebhookController extends Controller
{
    public function __construct(private readonly RazorpaySettings $settings) {}

    public function __invoke(Request $request): JsonResponse
    {
        $signature = (string) $request->header('X-Razorpay-Signature', '');
        $body = $request->getContent();

        abort_unless($signature !== '' && $this->hasValidSignature($body, $signature), 401, 'Invalid webhook signature.');

        $payload = json_decode($body, true);

        if (is_array($payload)) {
            ProcessRazorpayWebhook::dispatch($payload);
        }

        return response()->json(['received' => true]);
    }

    private function hasValidSignature(string $body, string $signature): bool
    {
        foreach ($this->settings->webhookSecrets() as $secret) {
            if (hash_equals(hash_hmac('sha256', $body, $secret), $signature)) {
                return true;
            }
        }

        return false;
    }
}
