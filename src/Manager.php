<?php

namespace Aimeos\AnalyticsBridge;

use Aimeos\AnalyticsBridge\Contracts\Driver;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;


class Manager
{
    protected Driver $driver;


    public function driver(string $name = null): self
    {
        if (!isset($this->driver))
        {
            $name ??= config('analytics.default');
            $class = '\\Aimeos\\AnalyticsBridge\\Drivers\\' . ucfirst($name);

            if (!class_exists($class)) {
                throw new InvalidArgumentException("Driver [$name] not found");
            }

            $this->driver = new $class(config('analytics.drivers.' . $name, []));
        }

        return $this;
    }


    public function pageViews(string $path, int $days = 30): array
    {
        return $this->driver()->pageViews($path, $days);
    }


    public function visits(string $path, int $days = 30): array
    {
        return $this->driver()->visits($path, $days);
    }


    public function visitDurations(string $path, int $days = 30): array
    {
        return $this->driver()->visitDurations($path, $days);
    }


    public function countries(string $path, int $days = 30): array
    {
        return $this->driver()->countries($path, $days);
    }


    public function referrers(string $path, int $days = 30): array
    {
        return $this->driver()->referrers($path, $days);
    }


    public function performance(string $url): ?array
    {
        $response = Http::get('https://pagespeedonline.googleapis.com/v5/runPagespeed', [
            'key' => config('analytics-bridge.pagespeed.apikey', ''),
            'fields' => 'loadingExperience',
            'strategy' => 'mobile',
            'url' => $url,
        ]);

        if (empty($data = $response->json('loadingExperience.metrics'))) {
            return null;
        }

        return [
            'ttfb' => [
                'value' => $metrics['FIRST_BYTE_MS']['percentile'] ?? null,
                'category' => $metrics['FIRST_BYTE_MS']['category'] ?? null,
            ],
            'fcp' => [
                'value' => $metrics['FIRST_CONTENTFUL_PAINT_MS']['percentile'] ?? null,
                'category' => $metrics['FIRST_CONTENTFUL_PAINT_MS']['category'] ?? null,
            ],
            'lcp' => [
                'value' => $metrics['LARGEST_CONTENTFUL_PAINT_MS']['percentile'] ?? null,
                'category' => $metrics['LARGEST_CONTENTFUL_PAINT_MS']['category'] ?? null,
            ],
            'fid' => [
                'value' => $metrics['FIRST_INPUT_DELAY_MS']['percentile'] ?? null,
                'category' => $metrics['FIRST_INPUT_DELAY_MS']['category'] ?? null,
            ],
            'cls' => [
                'value' => $metrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['percentile'] ?? null,
                'category' => $metrics['CUMULATIVE_LAYOUT_SHIFT_SCORE']['category'] ?? null,
            ],
        ];
    }


    public function all(string $url, int $days = 30): array
    {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';

        return [
            'pageViews' => $this->pageViews($path, $days),
            'visits' => $this->visits($path, $days),
            'visitDuration' => $this->visitDurations($path, $days),
            'countries' => $this->countries($path, $days),
            'referrers' => $this->referrers($path, $days),
            'performance' => $this->performance($url),
        ];
    }
}
