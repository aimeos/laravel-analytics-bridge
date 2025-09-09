<?php

namespace Aimeos\AnalyticsBridge;

use Aimeos\AnalyticsBridge\Contracts\Driver;
use Illuminate\Support\Facades\Http;
use Google\Service\SearchConsole;
use Google\Client;


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


    public function stats(string $url, int $days = 30, array $types = []): ?array
    {
        return $this->driver()->stats($url, $days, $types);
    }


    public function pagespeed(string $url, array $config = []): ?array
    {
        if (!$url) {
            throw new \InvalidArgumentException('URL must be a non-empty string');
        }

        $config = array_replace(config('analytics-bridge.crux', []), $config);

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


    public function indexed(string $url, string $lang = 'en', array $config = []): ?string
    {
        if (!$url) {
            throw new \InvalidArgumentException('URL must be a non-empty string');
        }

        $config = array_replace(config('analytics-bridge.gsc', []), $config);

        if (!isset($config['auth'])) {
            return null;
        }

        $parts = parse_url($url);
        $siteUrl = $parts['scheme'] . '://' . $parts['host'];
        $siteUrl .= isset($parts['port']) ? ':' . $parts['port'] : '';
        $siteUrl .= '/';

        $client = new Client();
        $client->setAuthConfig($config['auth']);
        $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

        $request = new InspectUrlIndexRequest([
            'languageCode' => $lang,
            'inspectionUrl' => $url,
            'siteUrl' => $siteUrl,
        ]);

        $response = $service->urlInspection_index->inspect($request);
        $result = $response->getInspectionResult();
        $status = $result->getIndexStatusResult()->getCoverageState();

        return $status;
    }


    public function search(string $url, int $days = 30, array $config = []): ?array
    {
        if (!$url) {
            throw new \InvalidArgumentException('URL must be a non-empty string');
        }

        $config = array_replace(config('analytics-bridge.gsc', []), $config);

        if (!isset($config['auth'])) {
            return null;
        }

        $parts = parse_url($url);
        $siteUrl = $parts['scheme'] . '://' . $parts['host'];
        $siteUrl .= isset($parts['port']) ? ':' . $parts['port'] : '';
        $siteUrl .= '/';

        $client = new Client();
        $client->setAuthConfig($config['auth']);
        $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

        $service = new SearchConsole($client);
        $request = new SearchConsole\SearchAnalyticsQueryRequest([
            'startDate' => now()->subDays($days)->toDateString(),
            'endDate' => now()->toDateString(),
            'dimensions' => ['date'],
            'dimensionFilterGroups' => [[
                'groupType' => 'and',
                'filters' => [
                    [
                        'dimension' => 'page',
                        'operator' => 'equals',
                        'expression' => $url,
                    ]
                ]
            ]],
        ]);

        $response = $service->searchanalytics->query($siteUrl, $request);
        $data = [];

        foreach ($response->getRows() as $row) {
            $key = $row->getKeys()[0];
            $data['impressions'][] = ['key' => $key, 'value' => $row->getImpressions()];
            $data['clicks'][] = ['key' => $key, 'value' => $row->getClicks()];
            $data['ctrs'][] = ['key' => $key, 'value' => $row->getCtr()];
        }

        return $data;
    }


    public function queries(string $url, int $days = 30, array $config = []): ?array
    {
        if (!$url) {
            throw new \InvalidArgumentException('URL must be a non-empty string');
        }

        $config = array_replace(config('analytics-bridge.gsc', []), $config);

        if (!isset($config['auth'])) {
            return null;
        }

        $parts = parse_url($url);
        $siteUrl = $parts['scheme'] . '://' . $parts['host'];
        $siteUrl .= isset($parts['port']) ? ':' . $parts['port'] : '';
        $siteUrl .= '/';

        $client = new Client();
        $client->setAuthConfig($config['auth']);
        $client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

        $service = new SearchConsole($client);
        $request = new SearchConsole\SearchAnalyticsQueryRequest([
            'startDate' => now()->subDays($days)->toDateString(),
            'endDate' => now()->toDateString(),
            'dimensions' => ['query'], // Top queries
            'dimensionFilterGroups' => [[
                'groupType' => 'and',
                'filters' => [
                    [
                        'dimension' => 'page',
                        'operator' => 'equals',
                        'expression' => $url,
                    ]
                ]
            ]],
            'rowLimit' => 100
        ]);

        $response = $service->searchanalytics->query($siteUrl, $request);
        $data = [];

        foreach ($response->getRows() as $row) {
            $data[] = [
                'key' => $row->getKeys()[0],
                'impressions' => $row->getImpressions(),
                'clicks' => $row->getClicks(),
                'ctr' => $row->getCtr(),
                'position' => $row->getPosition()
            ];
        }

        return $data;
    }
}
