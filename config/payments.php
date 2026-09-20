<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Gateway
    |--------------------------------------------------------------------------
    |
    | Which driver App\Services\Payments\PaymentGatewayManager resolves when no
    | driver is named explicitly. "custom" talks to the remote gateway over
    | HTTP, "manual" marks orders for offline settlement, and "fake" is used by
    | the test suite so checkout can be exercised without any network calls.
    |
    */

    'default' => env('PAYMENT_GATEWAY', 'custom'),

    /*
    |--------------------------------------------------------------------------
    | Gateways
    |--------------------------------------------------------------------------
    */

    'gateways' => [

        'custom' => [
            'driver' => 'custom',
            'base_url' => env('PAYMENT_GATEWAY_BASE_URL'),
            'api_key' => env('PAYMENT_GATEWAY_API_KEY'),

            /*
             | Paths appended to base_url. Leave create_payment empty to POST
             | at base_url itself (for example when BASE_URL already includes
             | /api/orders). Fetch and refund paths are still required.
             */
            'endpoints' => [
                'create_payment' => env('PAYMENT_GATEWAY_CREATE_PATH', '/payments'),
                'fetch_payment' => env('PAYMENT_GATEWAY_FETCH_PATH', '/payments/{reference}'),
                'refund_payment' => env('PAYMENT_GATEWAY_REFUND_PATH', '/payments/{reference}/refund'),
            ],

            /*
             | The gateway authenticates us with a static API key. Set "scheme"
             | to "bearer" to send "Authorization: Bearer <key>" instead of a
             | bespoke header.
             */
            'auth' => [
                'scheme' => env('PAYMENT_GATEWAY_AUTH_SCHEME', 'header'),
                'header' => env('PAYMENT_GATEWAY_AUTH_HEADER', 'X-Api-Key'),
            ],

            /*
             | Maps our canonical field names onto the remote payload keys, and
             | maps the remote response back onto ours. Only these need to
             | change once the gateway's contract is confirmed.
             */
            'response_map' => [
                'reference' => env('PAYMENT_GATEWAY_RESPONSE_REFERENCE', 'id'),
                'redirect_url' => env('PAYMENT_GATEWAY_RESPONSE_REDIRECT', 'payment_url'),
                'status' => env('PAYMENT_GATEWAY_RESPONSE_STATUS', 'status'),
            ],

            /*
             | Remote status strings mapped onto App\Enums\PaymentStatus values.
             */
            'status_map' => [
                'paid' => 'paid',
                'success' => 'paid',
                'successful' => 'paid',
                'completed' => 'paid',
                'captured' => 'paid',
                'authorized' => 'authorized',
                'pending' => 'pending',
                'created' => 'pending',
                'processing' => 'pending',
                'failed' => 'failed',
                'declined' => 'failed',
                'error' => 'failed',
                'cancelled' => 'cancelled',
                'canceled' => 'cancelled',
                'refunded' => 'refunded',
            ],

            'timeout' => env('PAYMENT_GATEWAY_TIMEOUT', 30),
            'retries' => env('PAYMENT_GATEWAY_RETRIES', 2),
        ],

        'manual' => [
            'driver' => 'manual',
        ],

        'fake' => [
            'driver' => 'fake',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook
    |--------------------------------------------------------------------------
    |
    | Inbound webhooks authenticate with a static API key presented in a
    | header. The webhook is the source of truth for payment state; the
    | customer-facing callback only reads the state the webhook recorded.
    |
    */

    'webhook' => [
        'api_key' => env('PAYMENT_WEBHOOK_API_KEY'),
        'header' => env('PAYMENT_WEBHOOK_HEADER', 'X-Api-Key'),

        /*
         | Where the reference and status live inside the webhook payload, using
         | dot notation so nested payloads are supported.
         */
        'payload_map' => [
            'reference' => env('PAYMENT_WEBHOOK_REFERENCE_KEY', 'id'),
            'status' => env('PAYMENT_WEBHOOK_STATUS_KEY', 'status'),
            'amount' => env('PAYMENT_WEBHOOK_AMOUNT_KEY', 'amount'),
        ],
    ],

];
