<?php

namespace Aimeos\AnalyticsBridge\Contracts;

interface Driver
{
    public function pageViews(string $path, int $days = 30): array;
    public function visits(string $path, int $days = 30): array;
    public function visitDurations(string $path, int $days = 30): array;
    public function countries(string $path, int $days = 30): array;
    public function referrers(string $path, int $days = 30): array;
}
