<?php

namespace Aimeos\AnalyticsBridge\Contracts;

interface Driver
{
    public function all(string $url, int $days = 30): ?array;
    public function views(string $path, int $days = 30): ?array;
    public function visits(string $path, int $days = 30): ?array;
    public function durations(string $path, int $days = 30): ?array;
    public function countries(string $path, int $days = 30): ?array;
    public function referrers(string $path, int $days = 30): ?array;
}
