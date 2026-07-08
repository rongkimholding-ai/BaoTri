<div class="row">
    {{-- Hạng mục --}}
    <div class="col-md-4 mb-3">
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
                            data-solution="{{ $issue['solution'] }}"
                            @if(
                                (old('item_category') !== null && old('item_category') !== '' && old('severity') !== null && old('issue_description') !== null)
                                    ? (
                                        old('item_category') == $check['name'] &&
                                        old('severity') == $issue['severity'] &&
                                        old('issue_description') == $issue['name']
                                    )
                                    : (
                                        isset($maintenanceRequest->item_category, $maintenanceRequest->severity, $maintenanceRequest->issue_description)
                                        && $maintenanceRequest->item_category == $check['name']
                                        && $maintenanceRequest->severity == $issue['severity']
                                        && $maintenanceRequest->issue_description == $issue['name']
                                    )
                            ) selected @endif
                       
                       
                        >
                            {{ $issue['severity'] ? '('.$issue['severity'].') ' : '' }}{{ $check['name'] }} - {{ $issue['name'] }}
                        </option>
                    @endforeach
                </optgroup>
            @endforeach
        </select>
    </div>

    {{-- Thời gian hoàn thành --}}
    <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.standard_completion_time') }}</label>
        <input class="form-control processing-time" name="standard_completion_time"
            value="{{ old('standard_completion_time', $maintenanceRequest->standard_completion_time ?? '') }}">
    </div>

    {{-- Ngày làm --}}
    <div class="col-md-4 mb-3">
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
                        @checked(old($field, isset($maintenanceRequest->$field) ? (bool)$maintenanceRequest->$field : false))>
                    <label class="form-check-label" for="{{ $field }}">
                        {{ $label }}
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Mô tả sự cố --}}
    <div class="col-md-12 mb-3">
        <label>{{ config('maintenance.fields.issue_description') }}</label>
        <textarea rows="3" class="form-control issue-description" name="issue_description">{{ old('issue_description', $maintenanceRequest->issue_description ?? '') }}</textarea>
    </div>

    {{-- Giải pháp --}}
    <div class="col-md-12 mb-3">
        <label>{{ config('maintenance.fields.solution_description') }}</label>
        <textarea rows="3" class="form-control solution-description" name="solution_description">{!! old('solution_description', $maintenanceRequest->solution_description ?? '') !!}</textarea>
    </div>

    {{-- Hidden severity --}}
    <div class="col-md-6 mb-3 d-none">
        <label>{{ config('maintenance.fields.severity') }}</label>
        <select class="form-control severity-field select2-branch" name="severity">
            <option value="">-- Chọn {{ config('maintenance.fields.severity') }} --</option>
            @foreach($severities as $severity)
                <option value="{{ $severity['key'] }}" data-processing_time="{{ $severity['processing_time'] }}"
                    @if(old('severity', $maintenanceRequest->severity ?? '') == $severity['key']) selected @endif
                >
                    {{ $severity['name'] }}
                </option>
            @endforeach
        </select>
    </div>
</div>