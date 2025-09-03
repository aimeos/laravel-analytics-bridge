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
# for Google Analytics
composer require aimeos/laravel-analytics-google
# for Matomo
composer require aimeos/laravel-analytics-matomo
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
            'property_id' => env('ANALYTICS_GOOGLE_PROPERTY_ID'),
            'credentials' => storage_path('app/analytics/google.json'),
        ],

        'matomo' => [
            'url' => env('ANALYTICS_MATOMO_URL'),
            'site_id' => env('ANALYTICS_MATOMO_SITE_ID'),
            'token' => env('ANALYTICS_MATOMO_TOKEN'),
        ],
    ],

    'pagespeed' => [
        'api_key' => env('PAGESPEED_API_KEY'),
    ],
];
```

Set your .env accordingly.

## API Usage

Import the facade:

```php
use Aimeos\AnalyticsBridge\Facades\Analytics;
```

### Page Time Series

```php
// Number of page views for the last 30 days
$pageViews = Analytics::pageViews('/', 30);

// Number of visits for the last 30 days
$visits = Analytics::visits('/', 30);

// Average visit duration (seconds) per day
$durations = Analytics::visitDurations('/', 30);
```

Each returns arrays with one entry per day:

```php
[
    ['key' => '2025-08-01', 'value' => 123],
    ['key' => '2025-08-02', 'value' => 97],
    ...
]
```

### Top Aggregates

```php
// Top countries by visit count
$countries = Analytics::countries('/', 30);

// Top referrers by visit count
$referrers = Analytics::referrers('/', 30);
```

Each returns total counts over timeframe:

```php
[
    ['key' => 'Germany', 'value' => 321],
    ['key' => 'USA', 'value' => 244],
    ...
]
```

### Performance (Web Vitals)

```php
$performance = Analytics::performance('https://aimeos.org/features');
```

Returns:

```php
[
    // Time to first byte in milliseconds
    'ttfb' => ['value' => 200, 'category' => 'FAST'],
    // First contentful paint in milliseconds
    'fcp'  => ['value' => 1200, 'category' => 'FAST'],
    // Largest contentful paint in milliseconds
    'lcp'  => ['value' => 1800, 'category' => 'FAST'],
    // First input delay in milliseconds
    'fid'  => ['value' => 20, 'category' => 'FAST'],
    // Cumulative layout shift score value
    'cls'  => ['value' => 0.05, 'category' => 'FAST'],
]
```

Values are in milliseconds (except CLS, which is a score value).

### Combined Fetch

```php
$data = Analytics::fetch('https://aimeos.org/features', 30);
```

Returns:

```php
[
    'pageViews'     => [/* ... */],
    'visits'        => [/* ... */],
    'visitDuration' => [/* ... */],
    'countries'     => [/* ... */],
    'referrers'     => [/* ... */],
    'performance'   => [/* ... */],
]
```

## Implemnt new Driver

For a new analyics service (e.g. Foobar), create a new composer package, e.g.
`yourorg/laravel-analytics-foobar`. Replace every occurrence of "yourorg" and
"foobar" with own vendor name and resp. the service name.

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

    public function pageViews(string $path, int $days = 30): array
    {
        return [];
    }

    public function visits(string $path, int $days = 30): array
    {
        return [];
    }

    public function visitDurations(string $path, int $days = 30): array
    {
        return [];
    }

    public function countries(string $path, int $days = 30): array
    {
        return [];
    }

    public function referrers(string $path, int $days = 30): array
    {
        return [];
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
