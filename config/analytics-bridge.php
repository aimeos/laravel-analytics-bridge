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
];
