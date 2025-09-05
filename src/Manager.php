<?php

namespace Aimeos\AnalyticsBridge;

use Aimeos\AnalyticsBridge\Contracts\Driver;
use Illuminate\Support\Facades\Http;


class Manager implements Driver
{
    protected Driver $driver;


    public function driver(string $name = null, array $config = []): Driver
    {
        if (!isset($this->driver))
        {
            $name ??= config('analytics-bridge.default');
            $class = '\\Aimeos\\AnalyticsBridge\\Drivers\\' . ucfirst($name);

            if (!class_exists($class)) {
                throw new InvalidArgumentException("Driver [$name] not found");
            }

            $config = array_replace(config('analytics-bridge.drivers.' . $name, []), $config);

            $this->driver = new $class($config);
        }

        return $this->driver;
    }


    public function all(string $url, int $days = 30): ?array
    {
        return $this->driver()->all($url, $days);
    }


    public function views(string $url, int $days = 30): ?array
    {
        return $this->driver()->views($url, $days);
    }


    public function visits(string $url, int $days = 30): ?array
    {
        return $this->driver()->visits($url, $days);
    }


    public function durations(string $url, int $days = 30): ?array
    {
        return $this->driver()->durations($url, $days);
    }


    public function countries(string $url, int $days = 30): ?array
    {
        return $this->driver()->countries($url, $days);
    }


    public function referrers(string $url, int $days = 30): ?array
    {
        return $this->driver()->referrers($url, $days);
    }


    public function pagespeed(string $url, array $config = []): ?array
    {
        if (!$url) {
            throw new \InvalidArgumentException('URL must be a non-empty string');
        }

        $config   = array_replace(config('analytics-bridge.crux', []), $config);

        if (!isset($config['apikey'])) {
            return null;
        }

        $payload = ['url' => $url];

        if (isset($config['formFactor'])) {
            $payload['formFactor'] = $config['formFactor'];
        }

        $endpoint = 'https://chromeuxreport.googleapis.com/v1/records:queryRecord';
        $response = Http::post($endpoint . '?key=' . $config['apikey'], $payload);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch CrUX data: ' . $response->body());
        }

        $metrics = data_get($response->json(), 'record.metrics', []);

        return collect($metrics)
            ->map(fn($item, $key) => [
                'key' => !strncmp($key, 'experimental_', 13) ? substr($key, 13) : $key,
                'value' => $item['percentiles']['p75'] ?? null
            ])
            ->values()
            ->all();
    }
}
