<?php

namespace Database\Seeders;

use App\Models\HolidayCalendar;
use Illuminate\Database\Seeder;

class HolidayCalendarSeeder extends Seeder
{
    public function run(): void
    {
        $currentYear = now()->year;

        for ($year = $currentYear; $year <= $currentYear + 5; $year++) {

            $holidays = [
                [
                    'holiday_name' => 'Tết Dương Lịch',
                    'start_date' => "{$year}-01-01",
                    'end_date' => "{$year}-01-01",
                ],
                [
                    'holiday_name' => 'Ngày Giải Phóng Miền Nam',
                    'start_date' => "{$year}-04-30",
                    'end_date' => "{$year}-04-30",
                ],
                [
                    'holiday_name' => 'Quốc Tế Lao Động',
                    'start_date' => "{$year}-05-01",
                    'end_date' => "{$year}-05-01",
                ],
                [
                    'holiday_name' => 'Quốc Khánh',
                    'start_date' => "{$year}-09-02",
                    'end_date' => "{$year}-09-02",
                ],
            ];

            foreach ($holidays as $holiday) {

                HolidayCalendar::updateOrCreate(
                    [
                        'holiday_name' => $holiday['holiday_name'],
                        'start_date' => $holiday['start_date'],
                    ],
                    [
                        'end_date' => $holiday['end_date'],
                        'is_working_day' => false,
                    ]
                );
            }
        }
    }
}