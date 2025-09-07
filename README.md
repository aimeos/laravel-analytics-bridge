# Laravel Analytics Bridge

A unified analytics bridge for Laravel providing a consistent API across multiple analytics providers such as Google Analytics, Matomo, and others.

## Features

* Unified API for multiple analytics services
* Drivers for Google Analytics, Matomo, and others
* Returns time series (per day) for page views, visits, visit duration
* Aggregates top countries and referrers
* Integrates PageSpeed Insights (CrUX real-user data) for Web Vitals
* Easily extendable with new drivers

## Installation

Install the driver package you need:

```bash
# for Matomo
composer require aimeos/laravel-analytics-matomo
# for Google Analytics
composer require aimeos/laravel-analytics-google
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=config
```

This creates the `./config/analytics-bridge.php` file:

```php
return [
    'default' => env('ANALYTICS_DRIVER', 'matomo'),

    'drivers' => [
        'google' => [
            'propertyid' => env('GOOGLE_PROPERTYID'),
            'credentials' => storage_path('app/analytics/google.json'),
        ],

        'matomo' => [
            'url' => env('MATOMO_URL'),
            'token' => env('MATOMO_TOKEN'),
            'siteid' => env('MATOMO_SITEID'),
        ],
    ],

    'crux' => [
        'apikey' => env('CRUX_APIKEY')
    ]
];
```

Set your .env accordingly.

## API Usage

Import the facade:

```php
use Aimeos\AnalyticsBridge\Facades\Analytics;
```

### Page Statistics

Available are:

* views
* visits
* durations
* countries
* referrers

```php
$result = Analytics::stats('https://aimeos.org/features', 30);

// to limit the result set
$result = Analytics::stats('https://aimeos.org/features', 30, ['visits', 'referrers']);
```

It returns arrays with one entry per day, country or URL:

```php
[
    'views'     => [
        ['key' => '2025-08-01', 'value' => 123],
        ['key' => '2025-08-02', 'value' => 97],
        ...
    ],
    'visits'    => [
        ['key' => '2025-08-01', 'value' => 53],
        ['key' => '2025-08-02', 'value' => 40],
        ...
    ],
    'durations' => [ // in seconds
        ['key' => '2025-08-01', 'value' => 75],
        ['key' => '2025-08-02', 'value' => 80],
        ...
    ],
    'countries' => [
        ['key' => 'Germany', 'value' => 321],
        ['key' => 'USA', 'value' => 244],
        ...
    ],
    'referrers' => [
        ['key' => 'https://aimeos.org/', 'value' => 321],
        ['key' => 'https://aimeos.org/Laravel', 'value' => 244],
        ...
    ],
]
```

### PageSpeed Metrics


```php
$data = Analytics::pagespeed('https://aimeos.org/features');
```

Returns:

```php
[
    ['round_trip_time' => 150],
    ['time_to_first_byte' => 700],
    ['first_contentful_paint' => 1200],
    ['largest_contentful_paint' => 1700],
    ['interaction_to_next_paint' => 180],
    ['cumulative_layout_shift' => 0.05],
    /*...*/
]
```

## Implemnt new Driver

For a new analyics service (e.g. Foobar), create a new composer package, e.g.
`yourorg/laravel-analytics-foobar`. Replace every occurrence of "yourorg" and
"foobar" (in any case) with own vendor name and resp. the service name.

Use this `composer.json` as template:

```javascript
{
  "name": "yourorg/laravel-analytics-foobar",
  "description": "Foobar driver for Laravel Analytics Bridge",
  "type": "library",
  "license": "LGPL-2.1+",
  "autoload": {
    "psr-4": {
      "Aimeos\\AnalyticsBridge\\Drivers\\": "src/"
    }
  },
  "require": {
    "php": "^8.1",
    "aimeos/laravel-analytics-bridge": "~1.0"
  },
  "provide": {
    "aimeos/laravel-analytics-driver": "*"
  }
}
```

Create a `./src/Foobar.php` file that implements fetching the data from the
analytics service. The skeleton class is:

```php
<?php

namespace Aimeos\AnalyticsBridge\Drivers;

use Aimeos\AnalyticsBridge\Contracts\Driver;

class Foobar implements Driver
{
    public function __construct(array $config = [])
    {
        // $config from ./config/analytics-bridge.php
    }

    public function stats(string $path, int $days = 30, array $types = []): ?array
    {
        // limited by types if requested, or NULL if not available
        return [
            'views'     => [['key' => '2025-08-01', 'value' => 123], /*...*/],
            'visits'    => [['key' => '2025-08-01', 'value' => 123], /*...*/],
            'durations' => [['key' => '2025-08-01', 'value' => 123], /*...*/],
            'countries' => [['key' => 'Germany', 'value' => 321], /*...*/],
            'referrers' => [['key' => 'https://aimeos.org/', 'value' => 321], /*...*/],
        ];
    }
}
```

Install your package using composer:

```bash
composer require yourorg/laravel-analytics-foobar
```

Register your driver in `config/analytics-bridge.php`, e.g.:

```php
'drivers' => [
    'foobar' => [
        'url' => env('FOOBAR_URL'),
        // more required settings
    ]
],
```

## License

This package is released under the LGPL-2.1+ License.
