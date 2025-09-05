<?php

namespace Aimeos\AnalyticsBridge;

use Aimeos\AnalyticsBridge\Contracts\Driver;
use InvalidArgumentException;


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
}
