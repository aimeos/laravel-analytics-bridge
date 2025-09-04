<?php

namespace Aimeos\AnalyticsBridge\Contracts;

interface Driver
{
    public function all(string $url, int $days = 30): array;
    public function countries(string $url, int $days = 30): array;
    public function referrers(string $url, int $days = 30): array;
    public function pageViews(string $url, int $days = 30): array;
    public function visitDurations(string $url, int $days = 30): array;
    public function visits(string $url, int $days = 30): array;
}
