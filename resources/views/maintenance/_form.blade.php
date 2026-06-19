<div class="row">

    <div class="col-md-6 mb-3 hidden">
        <label>{{ config('maintenance.fields.branch_code') }}</label>
        <input class="form-control form-branch-code" name="branch_code">
    </div>

    <div class="col-md-6 mb-3 hidden">
        <label>{{ config('maintenance.fields.severity') }}</label>

        <select class="form-control severity-field select2-branch" name="severity">
            <option value="">-- Chọn {{ config('maintenance.fields.severity') }} --</option>

            @foreach($severities as $severity)
                <option value="{{ $severity['key'] }}"
                    data-processing_time="{{ $severity['processing_time'] }}">
                    {{ $severity['name'] }}
                </option>
            @endforeach
        </select>
    </div>

    @php
use Illuminate\Support\Facades\Auth;
$user = Auth::user();
// Tìm chi nhánh tương ứng với tài khoản đăng nhập.
$userStore = null;
if ($user) {
    // Tuỳ dữ liệu user, sửa lại cho đúng business (ở đây ví dụ so với email)
    // Do dữ liệu stores đã là ['mien_bac' => [], 'mien_nam' => []], phải loop cả 2 mảng để tìm userStore phù hợp
    $allStores = collect(($stores['mien_bac'] ?? []))->merge($stores['mien_nam'] ?? []);
    $userStore = $allStores->first(function ($store) use ($user) {
        return isset($store['email']) && $store['email'] === $user->email;
        // Hoặc nếu dùng branch_code:
        // return isset($store['code']) && $store['code'] === $user->branch_code;
    });
}
    @endphp

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
            @if(isset($stores['mien_bac']))
                <optgroup label="Miền Bắc">
                    @foreach($stores['mien_bac'] as $store)
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
            @if(isset($stores['mien_nam']))
                <optgroup label="Miền Nam">
                    @foreach($stores['mien_nam'] as $store)
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
            <option value="other_store" data-custom="1">Cửa hàng khác</option>
        </select>
        
        @if($userStore)
            <input type="hidden" name="branch_code" value="{{ $userStore['code'] }}">
            <input type="hidden" name="branch_email" value="{{ $userStore['email'] }}">
            <input type="hidden" name="technician_name" value="{{ $userStore['technician_name'] ?? '' }}">
        @endif
   
    </div>
    
    <!-- <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.request_date') }}</label>

        <input type="date" class="form-control form-request-date" name="request_date">
    </div> -->

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
                            {{ $check['name'] }} {{ $issue['severity'] ? '(' . $issue['severity'] . ')' : '' }} - {{ $issue['name'] }}
                        </option>

                    @endforeach

                </optgroup>

            @endforeach

        </select>
    </div>

    <div id="other_store_input_wrap" class="d-none d-flex mt-2 col-md-12 mb-3">
        <div class="col-md-4 mr-2">
            <input type="text" class="form-control mb-2" name="other_branch_name" id="other_branch_name"
                placeholder="Nhập tên cửa hàng">
        </div>
        <div class="col-md-4 mr-2">
            <input type="text" class="form-control mb-2" name="other_branch_code" id="other_branch_code"
                placeholder="Nhập mã cửa hàng">
        </div>
        <div class="col-md-4 mr-2">
            <input type="text" class="form-control" name="other_branch_email" id="other_branch_email"
                placeholder="Nhập email cửa hàng">
        </div>
    </div>

    <div class="col-md-12 mb-3">
        <label>{{ config('maintenance.fields.issue_description') }}</label>

        <textarea rows="3" class="form-control issue-description" name="issue_description"></textarea>
    </div>

    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.standard_completion_time') }}</label>

        <input class="form-control processing-time" name="standard_completion_time">
    </div>

    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.technician_name') }}</label>

        <select class="form-control form-technician-name select2-branch" name="technician_name">

            <option value="">-- {{ config('maintenance.fields.technician_name') }} --</option>

            @foreach($techs as $tech)
            @if ($tech['email'] != 'liemhoang.support.hcm@tocotocotea.com')
                <option value="{{ $tech['name'] }}"
                    data-email="{{ $tech['email'] }}" data-mobile="{{ $tech['mobile'] }}">
                    {{ $tech['name'] }}
                </option>
                @endif
            @endforeach

        </select>
    </div>

    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.technician_mobile') }}</label>

        <input name="technician_mobile" class="form-control technician-mobile">
    </div>

    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.technician_email') }}</label>

        <input name="technician_email" class="form-control technician-email">
    </div>

    <!-- <div class="col-md-3 mb-3">
        <label>{{ config('maintenance.fields.sla_status') }}</label>

        <select class="form-control" name="sla_status">

            <option value="Đúng hạn">Đúng hạn</option>
            <option value="Trễ hạn">Trễ hạn</option>

        </select>
    </div> -->
    <div class="col-md-6 mb-3">
        <label class="form-label d-block">
            Bao gồm ngày làm
        </label>

        <div class="d-flex gap-4">
            <div class="form-check">
                <input class="form-check-input"
                    type="checkbox"
                    name="include_saturday"
                    id="include_saturday"
                    value="1"
                    @checked(old('include_saturday'))>

                <label class="form-check-label" for="include_saturday">
                    Thứ 7
                </label>
            </div>

            <div class="form-check">
                <input class="form-check-input"
                    type="checkbox"
                    name="include_sunday"
                    id="include_sunday"
                    value="1"
                    @checked(old('include_sunday'))>

                <label class="form-check-label" for="include_sunday">
                    Chủ nhật
                </label>
            </div>

            <div class="form-check">
                <input class="form-check-input"
                    type="checkbox"
                    name="include_holiday"
                    id="include_holiday"
                    value="1"
                    @checked(old('include_holiday'))>

                <label class="form-check-label" for="include_holiday">
                    Ngày lễ
                </label>
            </div>
        </div>
    </div>

    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.outsourced_provider') }}</label>

        <input class="form-control outsourced-provider" name="outsourced_provider">
    </div>

    <div class="col-md-12 mb-3">
        <label>{{ config('maintenance.fields.solution_description') }}</label>

        <textarea rows="3" class="form-control solution-description" name="solution_description">{!! old('solution_description', isset($maintenance) ? $maintenance->solution_description : '') !!}</textarea>
   
    </div>

    <div class="col-md-6 mb-3 hidden">
        <label>Mail cơ sở</label>

        <input name="branch_email" class="form-control form-branch-email">
    </div>

    <!-- <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.actual_completion_date') }}</label>

        <input type="date" class="form-control" name="actual_completion_date">
    </div> -->

    <!-- <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.actual_duration') }}</label>

        <input class="form-control" name="actual_duration">
    </div> -->

    <div class="col-md-6 mb-3 hidden">
        <label>{{ config('maintenance.fields.delay_reason') }}</label>

        <input class="form-control" name="delay_reason">
    </div>

    <!-- <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.acceptance_result') }}</label>

        <input class="form-control" name="acceptance_result">
    </div> -->

    <!-- <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.acceptance_confirmed_by') }}</label>

        <input class="form-control" name="acceptance_confirmed_by">
    </div> -->

</div>