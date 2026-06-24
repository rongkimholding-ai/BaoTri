<?php

namespace App\Http\Controllers;

use App\Models\HolidayCalendar;
use Illuminate\Http\Request;

class HolidayCalendarController extends Controller
{
    public function index(Request $request)
    {
        $holidays = HolidayCalendar::orderBy('start_date')->paginate(20);
        return view('holiday-calendars.index', [
            'holidays' => $holidays,
            'title' => 'Quản lý ngày lễ'
        ]);
    }

    public function create()
    {
        return view('holiday-calendars.create', [
            'title' => 'Thêm mới ngày lễ'
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'holiday_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_working_day' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $data['is_working_day'] = $request->boolean('is_working_day');

        HolidayCalendar::create($data);

        return redirect()
            ->route('holiday-calendars.index')
            ->with('success', 'Đã tạo ngày lễ.');
    }

    public function edit(HolidayCalendar $holidayCalendar)
    {
        return view('holiday-calendars.edit', [
            'holidayCalendar' => $holidayCalendar,
            'title' => 'Cập nhật ngày lễ'
        ]);
    }

    public function update(Request $request, HolidayCalendar $holidayCalendar)
    {
        $data = $request->validate([
            'holiday_name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_working_day' => 'nullable|boolean',
            'description' => 'nullable|string',
        ]);

        $data['is_working_day'] = $request->boolean('is_working_day');

        $holidayCalendar->update($data);

        return redirect()
            ->route('holiday-calendars.index')
            ->with('success', 'Đã cập nhật.');
    }

    public function destroy(HolidayCalendar $holidayCalendar)
    {
        $holidayCalendar->delete();

        return redirect()
            ->route('holiday-calendars.index')
            ->with('success', 'Đã xóa.');
    }
}