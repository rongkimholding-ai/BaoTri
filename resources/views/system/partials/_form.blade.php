@php
    // Lấy danh sách các issues từ file JSON resources/json/it_checks.json
    $issueOptions = [];
    $issueJsonPath = resource_path('json/it_checks.json');
    if (file_exists($issueJsonPath)) {
        $services = json_decode(file_get_contents($issueJsonPath), true);
        foreach ($services as $service) {
            $issues = $service['issues'] ?? [];
            $groupName = $service['name'];
            foreach ($issues as $issue) {
                $issueOptions[$groupName][] = [
                    'key' => $issue['key'],
                    'name' => $issue['name'],
                    'processing_time' => $issue['processing_time'],
                    'detail' => $issue['detail'] ?? $issue['name'],
                ];
            }
        }
    }
    $user = Auth::user();
    $userStore = null;
    $allStores = collect(($stores['mien_bac'] ?? []))->merge($stores['mien_nam'] ?? [])->merge($stores['cici_mien_nam'] ?? []);
    if ($user) {
        $userStore = $allStores->first(fn($store) => isset($store['email']) && $store['email'] === $user->email);
    }
@endphp
<div class="row">
    {{-- Hidden branch_code --}}
    <div class="col-md-6 mb-3 d-none">
        <label>{{ config('system.fields.branch_code') }}</label>
        <input class="form-control form-branch-code" name="branch_code" value="{{ old('branch_code', $maintenanceSystem->branch_code ?? $userStore['code'] ?? '') }}">
    </div>

    {{-- Tên sự cố / dịch vụ --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('system.fields.issue_name') ?? 'Tên sự cố / Dịch vụ' }} <span class="text-red-500">*</span></label>
        <select class="form-control select2-issue-name" name="issue_name" required>
            <option value="">-- Chọn sự cố / dịch vụ --</option>
            @foreach($issueOptions as $group => $issues)
                <optgroup label="{{ $group }}">
                    @foreach($issues as $issue)
                        <option value="{{ $issue['name'] }}"
                            data-code="{{ $issue['key'] }}"
                            data-sla="{{ $issue['processing_time'] }}"
                            data-issue_description="{{ $issue['detail'] }}"
                                @if(old('issue_name', $maintenanceSystem->issue_name ?? '') == $issue['name']) selected @endif
                        >{{ $issue['name'] }}</option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    {{-- Mã lỗi --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('system.fields.issue_code') ?? 'Mã lỗi' }}</label>
        <input class="form-control issue-code-system" name="issue_code" value="{{ old('issue_code', $maintenanceSystem->issue_code ?? '') }}">
    </div>

    {{-- Mô tả sự cố --}}
    <div class="col-md-12 mb-3">
        <label>{{ config('system.fields.issue_description') ?? 'Mô tả sự cố' }}</label>
        <textarea rows="3" class="form-control issue-description-system" name="issue_description">{{ old('issue_description', $maintenanceSystem->issue_description ?? '') }}</textarea>
    </div>

    {{-- Thời gian hoàn thành chuẩn --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('system.fields.standard_completion_time') ?? 'Thời gian chuẩn (SLA)' }}</label>
        <input class="form-control processing-time-system" name="standard_completion_time" value="{{ old('standard_completion_time', $maintenanceSystem->standard_completion_time ?? '') }}">
    </div>

    {{-- Thông tin chi nhánh hệ thống --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('system.fields.branch_name') ?? 'Chi nhánh hệ thống' }}</label>
        <select 
            class="form-control form-branch-name select2-branch"
            name="branch_name"
            id="branch_name_select"
            @if($userStore) 
                disabled 
                tabindex="-1" 
                style="pointer-events: none; background: #eee;"
            @endif
        >
            <option value="">-- Chọn cơ sở --</option>
            @foreach(['mien_bac'=>'Miền Bắc','mien_nam'=>'Miền Nam','cici_mien_nam' => 'Cici Miền Nam'] as $mien=>$label)
                @if(!empty($stores[$mien]))
                    <optgroup label="{{ $label }}">
                        @foreach($stores[$mien] as $store)
                            <option value="{{ $store['name'] }}"
                                data-branch_code="{{ $store['code'] }}"
                                data-branch_email="{{ $store['email'] }}"
                                @if(old('branch_name', $maintenanceSystem->branch_name ?? ($userStore['name'] ?? '')) == $store['name']) selected @endif
                            >
                                {{ $store['name'] }}
                            </option>
                        @endforeach
                    </optgroup>
                @endif
            @endforeach
            <option value="other_store" data-custom="1" @if(old('branch_name')=='other_store') selected @endif>Cửa hàng khác</option>
        </select>
        @if($userStore)
            <input type="hidden" name="branch_code" value="{{ $userStore['code'] }}">
            <input type="hidden" name="branch_email" value="{{ $userStore['email'] }}">
        @endif
    </div>

    {{-- Input cho "cửa hàng khác" --}}
    <div id="other_store_input_wrap" class="d-none d-flex mt-2 col-md-12 mb-3">
        @foreach(['other_branch_name'=>'Nhập tên cửa hàng', 'other_branch_code'=>'Nhập mã cửa hàng', 'other_branch_email'=>'Nhập email cửa hàng'] as $id=>$placeholder)
            <div class="col-md-4 mr-2">
                <input type="text" class="form-control mb-2" name="{{ $id }}" id="{{ $id }}" placeholder="{{ $placeholder }}" value="{{ old($id) }}">
            </div>
        @endforeach
    </div>

    {{-- Hidden branch email --}}
    <div class="col-md-6 mb-3 d-none">
        <label>Mail cơ sở</label>
        <input name="branch_email" class="form-control form-branch-email" value="{{ old('branch_email', $maintenanceSystem->branch_email ?? '') }}">
    </div>

    {{-- Tên kỹ thuật viên --}}
    <div class="col-md-4 mb-3">
        <label>{{ config('system.fields.technician_name') ?? 'Tên kỹ thuật viên' }}</label>
        <select class="form-control form-technician-name select2-branch" name="technician_name">
            <option value="">-- {{ config('system.fields.technician_name') ?? 'Kỹ thuật viên' }} --</option>
            @foreach($techs as $tech)
                @if ($tech['email'] != 'liemhoang.support.hcm@tocotocotea.com')
                    <option value="{{ $tech['name'] }}" data-email="{{ $tech['email'] }}" data-mobile="{{ $tech['mobile'] }}"
                        @if(old('technician_name', $maintenanceSystem->technician_name ?? '') == $tech['name']) selected @endif>
                        {{ $tech['name'] }}
                    </option>
                @endif
            @endforeach
        </select>
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('system.fields.technician_mobile') ?? 'SĐT KTV quản lý hệ thống' }}</label>
        <input name="technician_mobile" class="form-control technician-mobile" value="{{ old('technician_mobile', $maintenanceSystem->technician_mobile ?? '') }}">
    </div>

    {{-- Email kỹ thuật viên --}}
    <div class="col-md-4 mb-3">
        <label>{{ config('system.fields.technician_email') ?? 'Email kỹ thuật viên' }}</label>
        <input name="technician_email" class="form-control technician-email" value="{{ old('technician_email', $maintenanceSystem->technician_email ?? '') }}">
    </div>


    {{-- Giải pháp khắc phục --}}
    <div class="col-md-12 mb-3">
        <label>{{ config('system.fields.solution_description') ?? 'Giải pháp khắc phục' }}</label>
        <textarea rows="3" class="form-control solution-description" name="solution_description">{!! old('solution_description', $maintenanceSystem->solution_description ?? '') !!}</textarea>
    </div>

    {{-- Hidden delay_reason --}}
    <div class="col-md-6 mb-3 d-none">
        <label>{{ config('system.fields.delay_reason') }}</label>
        <input class="form-control" name="delay_reason" value="{{ old('delay_reason', $maintenanceSystem->delay_reason ?? '') }}">
    </div>

    {{-- Các trường đã ẩn khác giữ lại dưới dạng comment để sau dùng lại --}}
    {{-- 
    <div class="col-md-4 mb-3">
        <label>{{ config('system.fields.actual_completion_date') }}</label>
        <input type="date" class="form-control" name="actual_completion_date" value="{{ old('actual_completion_date', $maintenanceSystem->actual_completion_date ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('system.fields.created_by') }}</label>
        <input class="form-control" name="created_by" value="{{ old('created_by', $maintenanceSystem->created_by ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('system.fields.updated_by') }}</label>
        <input class="form-control" name="updated_by" value="{{ old('updated_by', $maintenanceSystem->updated_by ?? '') }}">
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('system.fields.completed_by') }}</label>
        <input class="form-control" name="completed_by" value="{{ old('completed_by', $maintenanceSystem->completed_by ?? '') }}">
    </div>
    <div class="col-md-3 mb-3">
        <label>{{ config('system.fields.status') }}</label>
        <select class="form-control" name="status">
            <option value="pending" @if(old('status', $maintenanceSystem->status ?? '') == 'pending') selected @endif>Chờ thực hiện</option>
            <option value="processing" @if(old('status', $maintenanceSystem->status ?? '') == 'processing') selected @endif>Đang xử lý</option>
            <option value="completed" @if(old('status', $maintenanceSystem->status ?? '') == 'completed') selected @endif>Đã hoàn thành</option>
        </select>
    </div>
    --}}

</div>