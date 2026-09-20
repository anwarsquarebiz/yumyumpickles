<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:8080'),
        'http://localhost:8080',
        'http://127.0.0.1:8080',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => ['X-Cart-Token'],
    'max_age' => 0,
    'supports_credentials' => true,
];
