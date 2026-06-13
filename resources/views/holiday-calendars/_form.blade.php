<div class="row">

    <div class="col-md-6 mb-3">
        <label>Tên ngày lễ</label>

        <input
            type="text"
            name="holiday_name"
            class="form-control"
            value="{{ old('holiday_name', $holidayCalendar->holiday_name ?? '') }}"
            required
        >
    </div>

    <div class="col-md-3 mb-3">
        <label>Từ ngày</label>

        <input
            type="date"
            name="start_date"
            class="form-control"
            value="{{ old('start_date', isset($holidayCalendar) ? $holidayCalendar->start_date->format('Y-m-d') : '') }}"
            required
        >
    </div>

    <div class="col-md-3 mb-3">
        <label>Đến ngày</label>

        <input
            type="date"
            name="end_date"
            class="form-control"
            value="{{ old('end_date', isset($holidayCalendar) ? $holidayCalendar->end_date->format('Y-m-d') : '') }}"
            required
        >
    </div>

    <div class="col-md-12 mb-3">

        <label class="d-block">
            Loại ngày
        </label>

        <div class="form-check">
            <input
                type="checkbox"
                class="form-check-input"
                name="is_working_day"
                value="1"
                @checked(old(
                    'is_working_day',
                    $holidayCalendar->is_working_day ?? false
                ))
            >

            <label class="form-check-label">
                Là ngày làm việc (làm bù)
            </label>
        </div>

    </div>

    <div class="col-md-12 mb-3">
        <label>Ghi chú</label>

        <textarea
            name="description"
            rows="3"
            class="form-control"
        >{{ old('description', $holidayCalendar->description ?? '') }}</textarea>
    </div>

</div>