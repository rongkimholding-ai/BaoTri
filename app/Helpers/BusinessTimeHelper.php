<?php

namespace App\Helpers;

use App\Models\HolidayCalendar;
use Carbon\Carbon;

class BusinessTimeHelper
{
    const WORK_START_HOUR = 8;
    const WORK_END_HOUR = 17;

    /**
     * Tính số giây làm việc thực tế
     */
    public static function diffInBusinessSeconds(
        Carbon|string $start,
        Carbon|string $end,
        bool $includeSaturday = true,
        bool $includeSunday = false,
        bool $includeHoliday = false,
        ?string $severity = null
    ): int {

        $start = $start instanceof Carbon
            ? $start->copy()
            : Carbon::parse($start);

        $end = $end instanceof Carbon
            ? $end->copy()
            : Carbon::parse($end);

        if ($start->gte($end)) {
            return 0;
        }

        /**
         * CASE OVERRIDE:
         * severity = 1A => tính full time, bỏ working hours
         */
        if ($severity === '1A') {
            return $start->diffInSeconds($end);
        }

        $seconds = 0;
        $current = $start->copy();

        while ($current->lt($end)) {

            $date = $current->toDateString();

            $calendar = HolidayCalendar::query()
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->first();

            if ($calendar) {

                if (!$includeHoliday && !$calendar->is_working_day) {
                    $current->addDay()->startOfDay();
                    continue;
                }

            } else {

                if (!$includeSaturday && $current->isSaturday()) {
                    $current->addDay()->startOfDay();
                    continue;
                }

                if (!$includeSunday && $current->isSunday()) {
                    $current->addDay()->startOfDay();
                    continue;
                }
            }

            $dayStart = $current->copy()->setTime(self::WORK_START_HOUR, 0, 0);
            $dayEnd   = $current->copy()->setTime(self::WORK_END_HOUR, 0, 0);

            $from = $current->greaterThan($dayStart)
                ? $current
                : $dayStart;

            $to = $end->lessThan($dayEnd)
                ? $end
                : $dayEnd;

            if ($from->lt($to)) {
                $seconds += $from->diffInSeconds($to);
            }

            $current->addDay()->startOfDay();
        }

        return $seconds;
    }

    /**
     * Format giây -> HH:MM:SS
     */
    public static function formatDuration(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf(
            '%02d:%02d:%02d',
            $hours,
            $minutes,
            $remainingSeconds
        );
    }
}