@php
// Chuẩn hóa biến $standardCompletionTimes từ config/real_time để dùng ở form
$standardCompletionTimes = config('real_time');
@endphp
<div class="row">
    {{-- Thông tin sự cố --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.issue_code') }} <span class="text-red-500">*</span></label>
        <input type="text" class="form-control" name="issue_code" value="{{ old('issue_code', $maintenanceSystem->issue_code ?? '') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.issue_name') }} <span class="text-red-500">*</span></label>
        <input type="text" class="form-control" name="issue_name" value="{{ old('issue_name', $maintenanceSystem->issue_name ?? '') }}" required>
    </div>
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.standard_completion_time') ?? 'SLA' }}</label>
        <select class="form-control" name="actual_completion_date">
            @foreach ($standardCompletionTimes as $key => $value)
                <option value="{{ $value['key'] }}" @selected(old('completion_time_code', $maintenanceSystem->completion_time_code ?? '') == $key)>
                    {{ $value['name'] ?? $key }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Thông tin chi nhánh & kỹ thuật viên --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.branch_name') }}</label>
        <select class="form-control select2-branch" name="branch_name" id="branch_name_select">
            <option value="">-- Chọn cơ sở --</option>
            @foreach(['mien_bac'=>'Miền Bắc','mien_nam'=>'Miền Nam'] as $mien=>$label)
                @if(!empty($stores[$mien] ?? []))
                    <optgroup label="{{ $label }}">
                        @foreach($stores[$mien] as $store)
                            <option value="{{ $store['name'] }}"
                                data-branch_email="{{ $store['email'] }}"
                                data-branch_code="{{ $store['code'] }}"
                                @if(old('branch_name', $maintenanceSystem->branch_name ?? '') == $store['name']) selected @endif
                            >{{ $store['name'] }}</option>
                        @endforeach
                    </optgroup>
                @endif
            @endforeach
            <option value="other_store" data-custom="1" @if(old('branch_name')=='other_store') selected @endif>Cửa hàng khác</option>
        </select>
        <input type="hidden" name="branch_code" value="{{ old('branch_code', $maintenanceSystem->branch_code ?? '') }}">
        <input type="hidden" name="branch_email" value="{{ old('branch_email', $maintenanceSystem->branch_email ?? '') }}">
    </div>
    <div id="other_store_input_wrap" class="d-none">
        <div class="row">
            <div class="col-md-4 mb-3">
                <label for="other_branch_name">Tên cửa hàng</label>
                <input type="text" class="form-control" name="other_branch_name" id="other_branch_name" placeholder="Nhập tên cửa hàng" value="{{ old('other_branch_name') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="other_branch_code">Mã cửa hàng</label>
                <input type="text" class="form-control" name="other_branch_code" id="other_branch_code" placeholder="Nhập mã cửa hàng" value="{{ old('other_branch_code') }}">
            </div>
            <div class="col-md-4 mb-3">
                <label for="other_branch_email">Email cửa hàng</label>
                <input type="text" class="form-control" name="other_branch_email" id="other_branch_email" placeholder="Nhập email cửa hàng" value="{{ old('other_branch_email') }}">
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.technician_name') }}</label>
        <select class="form-control form-technician-name select2-branch" name="technician_name" id="technician_name_select">
            <option value="">-- {{ config('maintenance.fields.technician_name') }} --</option>
            @foreach($techs ?? [] as $tech)
                @if ($tech['email'] != 'liemhoang.support.hcm@tocotocotea.com')
                    <option value="{{ $tech['name'] }}"
                        data-email="{{ $tech['email'] }}"
                        data-mobile="{{ $tech['mobile'] }}"
                        @if(old('technician_name', $maintenanceSystem->technician_name ?? '') == $tech['name']) selected @endif
                    >{{ $tech['name'] }}</option>
                @endif
            @endforeach
        </select>
        <input type="hidden" name="technician_email" value="{{ old('technician_email', $maintenanceSystem->technician_email ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.technician_mobile') }}</label>
        <input name="technician_mobile" class="form-control technician-mobile" value="{{ old('technician_mobile', $maintenanceSystem->technician_mobile ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.technician_email') }}</label>
        <input name="technician_email" class="form-control technician-email" value="{{ old('technician_email', $maintenanceSystem->technician_email ?? '') }}">
    </div>

    {{-- Thông tin diễn giải & khắc phục --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.issue_description') }}</label>
        <textarea class="form-control" rows="3" name="issue_description">{{ old('issue_description', $maintenanceSystem->issue_description ?? '') }}</textarea>
    </div>
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.solution_description') }}</label>
        <textarea class="form-control" rows="3" name="solution_description">{{ old('solution_description', $maintenanceSystem->solution_description ?? '') }}</textarea>
    </div>

    

    {{-- Thông tin xử lý --}}
    <!-- <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.request_date') ?? 'Ngày yêu cầu' }}</label>
        <input type="datetime-local" class="form-control" name="request_at"
            value="{{ old('request_at', isset($maintenanceSystem) ? optional($maintenanceSystem->request_at)->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}">
    </div> -->
    <!-- <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.status') ?? 'Trạng thái' }}</label>
        <select class="form-control" name="status">
            @php
                $statusOptions = config('sla_status.code');
                $statuses = config('sla_status.names');
            @endphp
            @foreach($statusOptions as $key => $value)
                <option value="{{ $key }}" @selected(old('status',$maintenanceSystem->status ?? 'NEW') == $key)>
                    {{ $statuses[$key] ?? $key }}
                </option>
            @endforeach
        </select>
    </div> -->
    <!-- <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.delay_reason') ?? 'Lý do trễ' }}</label>
        <textarea class="form-control" rows="3" name="delay_reason">{{ old('delay_reason', $maintenanceSystem->delay_reason ?? '') }}</textarea>
    </div> -->
</div>