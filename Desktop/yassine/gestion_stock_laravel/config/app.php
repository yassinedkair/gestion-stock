<?php

return [
    'name' => env('APP_NAME', 'Gestion Stock'),
    'env' => env('APP_ENV', 'local'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Africa/Casablanca',
    'locale' => 'fr',
    'fallback_locale' => 'fr',
    'faker_locale' => 'fr_FR',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [ ...array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))) ],
    'maintenance' => [
        'driver' => 'file',
        'store' => 'database',
    ],
];
