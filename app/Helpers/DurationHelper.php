<?php

if (!function_exists('format_duration')) {

    function format_duration(?string $duration): ?string
    {
        if (empty($duration)) {
            return null;
        }
        // dd($duration);

        [$hours, $minutes, $seconds] = explode(':', $duration);

        $hours = (int) $hours;
        $minutes = (int) $minutes;
        $seconds = (int) $seconds;

        $days = floor($hours / 24);
        $hours = $hours % 24;

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days} ngày";
        }

        if ($hours > 0) {
            $parts[] = "{$hours} giờ";
        }

        if ($minutes > 0) {
            $parts[] = "{$minutes} phút";
        }

        if ($seconds > 0) {
            $parts[] = "{$seconds} giây";
        }

        return empty($parts)
            ? '0 giây'
            : implode(' ', $parts);
    }
}