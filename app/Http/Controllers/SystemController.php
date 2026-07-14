<?php

namespace App\Http\Controllers;

use App\Models\HolidayCalendar;
use Carbon\Carbon;

class SystemController extends Controller {
    public function calendarInfo()
    {
        $today = Carbon::today();
    
        $isHoliday = HolidayCalendar::query()
            ->where('is_working_day', false)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->exists();
    
        return response()->json([
            // 'today'        => $today->toDateString(),
            // 'day_of_week'  => $today->dayOfWeek, // 0 = CN, 6 = T7
            'is_saturday'  => $today->isSaturday(),
            'is_sunday'    => $today->isSunday(),
            'is_holiday'   => $isHoliday,
        ]);
    }
}

