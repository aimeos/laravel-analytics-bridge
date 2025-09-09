<?php

return [
    'default' => env('ANALYTICS_DRIVER', 'matomo'),

    'drivers' => [
        'matomo' => [
            'url' => env('MATOMO_URL'),
            'token' => env('MATOMO_TOKEN'),
            'siteid' => env('MATOMO_SITEID'),
        ]
    ],

    'crux' => [
        'apikey' => env('CRUX_APIKEY'),
    ],

    'gsc' => [
        'auth' => json_decode(base64_decode(env('GSC_AUTH', '')), true)
    ]
];
