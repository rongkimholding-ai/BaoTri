<div class="row">
    @php
        use Illuminate\Support\Facades\Auth;
        $user = Auth::user();
        $userStore = null;
        $allStores = collect(($stores['mien_bac'] ?? []))->merge($stores['mien_nam'] ?? []);
        if ($user) {
            $userStore = $allStores->first(fn($store) => isset($store['email']) && $store['email'] === $user->email);
        }
    @endphp

    {{-- Hidden branch_code --}}
    <div class="col-md-6 mb-3 d-none">
        <label>{{ config('maintenance.fields.branch_code') }}</label>
        <input class="form-control form-branch-code" name="branch_code">
    </div>

    {{-- Branch Name (select) --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.branch_name') }}</label>
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
            @foreach(['mien_bac'=>'Miền Bắc','mien_nam'=>'Miền Nam'] as $mien=>$label)
                @if(!empty($stores[$mien]))
                    <optgroup label="{{ $label }}">
                        @foreach($stores[$mien] as $store)
                            <option value="{{ $store['name'] }}"
                                data-branch_email="{{ $store['email'] }}"
                                data-technician_name="{{ $store['technician_name'] ?? '' }}"
                                data-code="{{ $store['code'] }}"
                                @if($userStore && $store['email'] === $userStore['email']) selected @endif
                            >
                                {{ $store['name'] }}
                            </option>
                        @endforeach
                    </optgroup>
                @endif
            @endforeach
            <option value="other_store" data-custom="1">Cửa hàng khác</option>
        </select>
        @if($userStore)
            <input type="hidden" name="branch_code" value="{{ $userStore['code'] }}">
            <input type="hidden" name="branch_email" value="{{ $userStore['email'] }}">
            <input type="hidden" name="technician_name" value="{{ $userStore['technician_name'] ?? '' }}">
        @endif
    </div>
    
    {{-- Hạng mục --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.item_category') }}</label>
        <select class="form-control select2-category issue-selector" name="item_category">
            <option value="">Chọn hạng mục</option>
            @foreach($checks as $check)
                <optgroup label="{{ $check['name'] }}">
                    @foreach($check['issues'] as $issue)
                        <option value="{{ $check['name'] }}" 
                            data-category="{{ $check['name'] }}" data-key="{{ $issue['key'] }}"  
                            data-issue="{{ $issue['name'] }}" data-severity="{{ $issue['severity'] }}"
                            data-handler="{{ $issue['handler'] }}" data-processing="{{ $issue['processing_time'] }}"
                            data-solution="{{ $issue['solution'] }}">
                            {{ $issue['severity'] ? '('.$issue['severity'].') ' : '' }}{{ $check['name'] }} - {{ $issue['name'] }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    {{-- Other store input --}}
    <div id="other_store_input_wrap" class="d-none d-flex mt-2 col-md-12 mb-3">
        @foreach(['other_branch_name'=>'Nhập tên cửa hàng', 'other_branch_code'=>'Nhập mã cửa hàng', 'other_branch_email'=>'Nhập email cửa hàng'] as $id=>$placeholder)
            <div class="col-md-4 mr-2">
                <input type="text" class="form-control mb-2" name="{{ $id }}" id="{{ $id }}" placeholder="{{ $placeholder }}">
            </div>
        @endforeach
    </div>

    {{-- Hidden severity --}}
    <div class="col-md-6 mb-3 d-none">
        <label>{{ config('maintenance.fields.severity') }}</label>
        <select class="form-control severity-field select2-branch" name="severity">
            <option value="">-- Chọn {{ config('maintenance.fields.severity') }} --</option>
            @foreach($severities as $severity)
                <option value="{{ $severity['key'] }}" data-processing_time="{{ $severity['processing_time'] }}">
                    {{ $severity['name'] }}
                </option>
            @endforeach
        </select>
    </div>

    {{-- Mô tả sự cố --}}
    <div class="col-md-12 mb-3">
        <label>{{ config('maintenance.fields.issue_description') }}</label>
        <textarea rows="3" class="form-control issue-description" name="issue_description"></textarea>
    </div>

    {{-- Thời gian hoàn thành --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.standard_completion_time') }}</label>
        <input class="form-control processing-time" name="standard_completion_time">
    </div>

    {{-- Tên kỹ thuật viên --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.technician_name') }}</label>
        <select class="form-control form-technician-name select2-branch" name="technician_name">
            <option value="">-- {{ config('maintenance.fields.technician_name') }} --</option>
            @foreach($techs as $tech)
                @if ($tech['email'] != 'liemhoang.support.hcm@tocotocotea.com')
                    <option value="{{ $tech['name'] }}" data-email="{{ $tech['email'] }}" data-mobile="{{ $tech['mobile'] }}">
                        {{ $tech['name'] }}
                    </option>
                @endif
            @endforeach
        </select>
    </div>

    {{-- SĐT kỹ thuật viên --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.technician_mobile') }}</label>
        <input name="technician_mobile" class="form-control technician-mobile">
    </div>

    {{-- Email kỹ thuật viên --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.technician_email') }}</label>
        <input name="technician_email" class="form-control technician-email">
    </div>

    {{-- Ngày làm --}}
    <div class="col-md-6 mb-3">
        <label class="form-label d-block">Bao gồm ngày làm</label>
        <div class="d-flex gap-4">
            @foreach([
                'include_saturday' => 'Thứ 7',
                'include_sunday' => 'Chủ nhật',
                'include_holiday' => 'Ngày lễ'
            ] as $field => $label)
                <div class="form-check">
                    <input class="form-check-input"
                        type="checkbox"
                        name="{{ $field }}"
                        id="{{ $field }}"
                        value="1"
                        @checked(old($field))>
                    <label class="form-check-label" for="{{ $field }}">
                        {{ $label }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Đơn vị outsource --}}
    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.outsourced_provider') }}</label>
        <input class="form-control outsourced-provider" name="outsourced_provider">
    </div>

    {{-- Giải pháp --}}
    <div class="col-md-12 mb-3">
        <label>{{ config('maintenance.fields.solution_description') }}</label>
        <textarea rows="3" class="form-control solution-description" name="solution_description">{!! old('solution_description', $maintenance->solution_description ?? '') !!}</textarea>
    </div>

    {{-- Hidden branch email --}}
    <div class="col-md-6 mb-3 d-none">
        <label>Mail cơ sở</label>
        <input name="branch_email" class="form-control form-branch-email">
    </div>

    {{-- Hidden delay_reason --}}
    <div class="col-md-6 mb-3 d-none">
        <label>{{ config('maintenance.fields.delay_reason') }}</label>
        <input class="form-control" name="delay_reason">
    </div>

    {{-- Các trường đã ẩn khác giữ lại dưới dạng comment để sau dùng lại --}}
    {{-- 
    <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.request_date') }}</label>
        <input type="date" class="form-control form-request-date" name="request_date">
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.actual_completion_date') }}</label>
        <input type="date" class="form-control" name="actual_completion_date">
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.actual_duration') }}</label>
        <input class="form-control" name="actual_duration">
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.acceptance_result') }}</label>
        <input class="form-control" name="acceptance_result">
    </div>
    <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.acceptance_confirmed_by') }}</label>
        <input class="form-control" name="acceptance_confirmed_by">
    </div>
    <div class="col-md-3 mb-3">
        <label>{{ config('maintenance.fields.sla_status') }}</label>
        <select class="form-control" name="sla_status">
            <option value="Đúng hạn">Đúng hạn</option>
            <option value="Trễ hạn">Trễ hạn</option>
        </select>
    </div>
    --}}
</div>