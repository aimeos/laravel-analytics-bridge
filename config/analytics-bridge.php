<?php

return [
    'default' => env('ANALYTICS_DRIVER', 'none'),

    'drivers' => [
        'matomo' => [
            'url' => env('MATOMO_URL'),
            'token' => env('MATOMO_TOKEN'),
            'siteid' => env('MATOMO_SITEID'),
        ]
    ],

    'google' => [
        'auth' => json_decode(base64_decode(env('GOOGLE_AUTH', '')), true),
        'crux' => [
            'apikey' => env('CRUX_API_KEY'),
        ]
    ]
];
