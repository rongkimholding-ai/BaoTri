<div class="row">

    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.branch_code') }}</label>
        <input class="form-control form-branch-code" name="branch_code">
    </div>

    <div class="col-md-6 mb-3">
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

    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.branch_name') }}</label>

        <select class="form-control form-branch-name select2-branch" name="branch_name">
            <option value="">-- Chọn cơ sở --</option>

            @foreach($stores as $store)
                <option value="{{ $store['name'] }}" data-technician_name="{{ $store['technician_name'] ?? '' }}"
                    data-code="{{ $store['code'] }}">
                    {{ $store['name'] }}
                </option>
            @endforeach
        </select>
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
                            {{ $check['name'] }} - {{ $issue['name'] }}
                        </option>

                    @endforeach

                </optgroup>

            @endforeach

        </select>
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
                <option value="{{ $tech['name'] }}"
                    data-email="{{ $tech['email'] }}" data-mobile="{{ $tech['mobile'] }}">
                    {{ $tech['name'] }}
                </option>
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

    <div class="col-md-12 mb-3">
        <label>{{ config('maintenance.fields.solution_description') }}</label>

        <textarea rows="3" class="form-control solution-description" name="solution_description"></textarea>
    </div>

    <!-- <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.actual_completion_date') }}</label>

        <input type="date" class="form-control" name="actual_completion_date">
    </div> -->

    <!-- <div class="col-md-4 mb-3">
        <label>{{ config('maintenance.fields.actual_duration') }}</label>

        <input class="form-control" name="actual_duration">
    </div> -->

    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.delay_reason') }}</label>

        <input class="form-control" name="delay_reason">
    </div>

    <div class="col-md-6 mb-3">
        <label>{{ config('maintenance.fields.outsourced_provider') }}</label>

        <input class="form-control outsourced-provider" name="outsourced_provider">
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