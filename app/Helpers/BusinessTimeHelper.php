<?php

namespace App\Helpers;

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
        Carbon|string $end
    ): int {
        $start = $start instanceof Carbon ? $start->copy() : Carbon::parse($start);
        $end = $end instanceof Carbon ? $end->copy() : Carbon::parse($end);

        if ($start->gte($end)) {
            return 0;
        }

        $seconds = 0;
        $current = $start->copy();

        while ($current->lt($end)) {

            // Nghỉ Chủ nhật
            if ($current->isSunday()) {
                $current->addDay()->startOfDay();
                continue;
            }

            $dayStart = $current->copy()->setTime(self::WORK_START_HOUR, 0, 0);
            $dayEnd = $current->copy()->setTime(self::WORK_END_HOUR, 0, 0);

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
}