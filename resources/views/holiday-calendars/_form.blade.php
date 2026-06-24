<div class="row g-3">

    <div class="col-md-6">
        <label for="holiday_name" class="form-label">Tên ngày lễ <span class="text-danger">*</span></label>
        <input
            type="text"
            name="holiday_name"
            id="holiday_name"
            class="form-control"
            value="{{ old('holiday_name', $holidayCalendar->holiday_name ?? '') }}"
            required
            autocomplete="off"
            maxlength="100"
            placeholder="VD: Giỗ Tổ Hùng Vương"
        >
    </div>

    <div class="col-md-3">
        <label for="start_date" class="form-label">Từ ngày <span class="text-danger">*</span></label>
        <input
            type="date"
            name="start_date"
            id="start_date"
            class="form-control"
            value="{{ old('start_date', isset($holidayCalendar) && $holidayCalendar->start_date ? $holidayCalendar->start_date->format('Y-m-d') : '') }}"
            required
            autocomplete="off"
        >
    </div>

    <div class="col-md-3">
        <label for="end_date" class="form-label">Đến ngày <span class="text-danger">*</span></label>
        <input
            type="date"
            name="end_date"
            id="end_date"
            class="form-control"
            value="{{ old('end_date', isset($holidayCalendar) && $holidayCalendar->end_date ? $holidayCalendar->end_date->format('Y-m-d') : '') }}"
            required
            autocomplete="off"
        >
    </div>

    <div class="col-md-12">
        <label class="form-label d-block mb-2">Loại ngày</label>
        <div class="form-check form-check-inline">
            <input
                type="checkbox"
                class="form-check-input"
                name="is_working_day"
                id="is_working_day"
                value="1"
                @checked(old('is_working_day', $holidayCalendar->is_working_day ?? false))
            >
            <label class="form-check-label" for="is_working_day">
                Là ngày làm việc (làm bù)
            </label>
        </div>
    </div>

    <div class="col-md-12">
        <label for="description" class="form-label">Ghi chú</label>
        <textarea
            name="description"
            id="description"
            rows="2"
            class="form-control"
            maxlength="255"
            placeholder="Nhập ghi chú (không bắt buộc)"
        >{{ old('description', $holidayCalendar->description ?? '') }}</textarea>
    </div>

</div>