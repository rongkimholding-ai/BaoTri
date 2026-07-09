<?php

namespace App\Services;

class RealTimeService
{
    public function getByKey(string $key): ?array
    {
        return collect(config('real_time'))
            ->firstWhere('key', $key);
    }
}