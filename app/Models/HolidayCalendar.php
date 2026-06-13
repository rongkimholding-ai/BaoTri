<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HolidayCalendar extends Model
{
    protected $fillable = [
        'holiday_name',
        'start_date',
        'end_date',
        'is_working_day',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_working_day' => 'boolean',
    ];
}