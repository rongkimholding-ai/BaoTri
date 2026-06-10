<div class="py-4">
        <div class="table-scroll-top">
            <div></div>
        </div>
        <div class="table-responsive">
            <table id="tbData" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>{{ config('maintenance.fields.id') }}</th>
                        <th>{{ config('maintenance.fields.branch_code') }}</th>
                        <th class="branch_name_class">{{ config('maintenance.fields.branch_name') }}</th>
                        <th>{{ config('maintenance.fields.request_date') }}</th>
                        <th>{{ config('maintenance.fields.item_category') }}</th>
                        <th>{{ config('maintenance.fields.issue_description') }}</th>
                        <th>{{ config('maintenance.fields.standard_completion_time') }}</th>
                        <th>{{ config('maintenance.fields.severity') }}</th>
                        <th>{{ config('maintenance.fields.technician_name') }}</th>
                        <th>{{ config('maintenance.fields.technician_mobile') }}</th>
                        <th>{{ config('maintenance.fields.solution_description') }}</th>
                        <th>{{ config('maintenance.fields.actual_completion_date') }}</th>
                        <th>{{ config('maintenance.fields.actual_duration') }}</th>
                        <th>{{ config('maintenance.fields.sla_status') }}</th>
                        <th>{{ config('maintenance.fields.delay_reason') }}</th>
                        <th>{{ config('maintenance.fields.outsourced_provider') }}</th>
                        <th>{{ config('maintenance.fields.acceptance_result') }}</th>
                        <th class="confirm_checked">{{ config('maintenance.fields.confirmed_checked') }}</th>
                        <th>{{ config('maintenance.fields.acceptance_confirmed_by') }}</th>
                        <th>{{ config('maintenance.fields.reminder') }}</th>
                        <th>{{ config('maintenance.fields.created_at') }}</th>
                        <th>{{ config('maintenance.fields.updated_at') }}</th>
                        <th class="action-column">Thao tác</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $canUpdate = auth()->user()->can('update data');
                    @endphp
                    @if (!empty($requests))
                    @foreach($requests as $item)
                        <tr data-id="{{ $item->id }}">
                            <td>{{ $item->id }}</td>
                            <td>
                                @if($canUpdate)
                                <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="branch_code"
                                    value="{{ $item->branch_code }}">
                                @else
                                    {{ $item->branch_code }}
                                @endif
                            </td>
                            <td class="branch_name_class">
                            @if($canUpdate)
                            <select class="form-control inline-edit select2-branch"
                                    data-id="{{ $item->id }}"
                                    data-field="branch_name">
                                <option value="">-- Chọn cơ sở --</option>

                                @foreach($stores as $store)
                                    <option value="{{ $store['name'] }}"
                                            data-code="{{ $store['code'] }}"
                                            @selected($item->branch_name == $store['name'])>
                                        {{ $store['name'] }}
                                    </option>
                                @endforeach
                            </select>
                                @else
                                    {{ $item->branch_name }}
                                @endif
                            </td>
                            <td>
                                @if($canUpdate)
                                <input class="form-control inline-edit date-field" data-id="{{ $item->id }}"
                                    data-field="request_date" type="datetime-local" value="{{ $item->request_date ? \Carbon\Carbon::parse($item->request_date)->format('Y-m-d\TH:i') : '' }}">
                                @else
                                    {{ $item->request_date ? \Carbon\Carbon::parse($item->request_date)->format('d/m/Y H:i') : '' }}
                                @endif
                            </td>
                            <td class="item_category_class">
                                @if($canUpdate)
                                <select class="form-control inline-edit select2-category issue-selector" data-id="{{ $item->id }}"
                                    data-field="item_category">
                                    <option value="">Chọn hạng mục</option>
                                    @foreach($checks as $check)
                                        <optgroup label="{{ $check['name'] }}">
                                            @foreach($check['issues'] as $issue)
                                                @php
                                                    $value = $check['key'] . '|' . $issue['key'];
                                                @endphp
                                                <option value="{{ $issue['name'] }}" data-category="{{ $check['name'] }}"
                                                    data-issue="{{ $issue['name'] }}" data-severity="{{ $issue['severity'] }}"
                                                    data-processing="{{ $issue['processing_time'] }}"
                                                    data-solution="{{ $issue['solution'] }}"
                                                    @selected($item->issue_description == $issue['name'])>
                                                    {{ $check['name'] }} - {{ $issue['name'] }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @else
                                    {{ $item->item_category }}
                                @endif
                            </td>
                            <td class="textarea-field">
                            @if($canUpdate)
                                <textarea class="form-control inline-edit issue-description" data-id="{{ $item->id }}"
                                    data-field="issue_description" rows="3">{{ $item->issue_description }}</textarea>
                                @else
                                {{ $item->issue_description }}
                                @endif
                            </td>
                            <td>
                            @if($canUpdate)
                                <input class="form-control inline-edit processing-time" data-id="{{ $item->id }}"
                                    data-field="standard_completion_time" value="{{ $item->standard_completion_time }}">
                                @else
                                    @php
                                        $realTimeList = json_decode(file_get_contents(resource_path('json/real_time.json')), true);
                                        $realTimeMap = collect($realTimeList)->keyBy('key');
                                        $timeName = isset($item->standard_completion_time) && $item->standard_completion_time
                                            ? ($realTimeMap[$item->standard_completion_time]['name'] ?? $item->standard_completion_time)
                                            : '';
                                    @endphp
                                    {{ $timeName }}
                               
                                @endif
                            </td>
                            <td>
                            @if($canUpdate)
                                <input class="form-control inline-edit severity-field" data-id="{{ $item->id }}"
                                    data-field="severity" value="{{ $item->severity }}">
                                @else
                                    {{ $item->severity }}
                                @endif
                            </td>
                            <td>
                            @if($canUpdate)
                            <select class="form-control inline-edit form-technician-name select2-branch" data-id="{{ $item->id }}"
                                    data-field="technician_name">
                                    <option value="">-- Chọn {{ config('maintenance.fields.technician_name') }} --</option>
                                    @foreach($techs as $tech)
                                        <option value="{{ $tech['name'] }}"
                                        data-mobile="{{ $tech['mobile'] }}"
                                        @if($item->technician_name == $tech['name']) selected @endif
                                        >
                                            {{ $tech['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                    @else
                                    {{ $item->technician_name }}
                                @endif
                            </td>

                            <td class="technician-mobile-field">
                            @if($canUpdate)
                                <input
                                    class="form-control inline-edit technician-mobile"
                                    data-id="{{ $item->id }}"
                                    data-field="technician_mobile"
                                    value="{{ $item->technician_mobile }}">
                            @else
                                {{ $item->technician_mobile }}
                            @endif
                            </td>

                            <td class="textarea-field">
                            @if($canUpdate)
                                <textarea class="form-control inline-edit solution-description" data-id="{{ $item->id }}"
                                    data-field="solution_description" rows="3">{{ $item->solution_description }}</textarea>
                                @else
                                {{ $item->solution_description }}
                                @endif
                            </td>

                            <td>
                                @if($canUpdate)
                                <input class="form-control inline-edit date-field" data-id="{{ $item->id }}"
                                    data-field="actual_completion_date" type="datetime-local" value="{{ $item->actual_completion_date }}">
                                @else
                                    {{ $item->actual_completion_date }}
                                @endif
                            </td>

                            <td>
                                @if($canUpdate)
                                <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="actual_duration"
                                    value="{{ $item->actual_duration }}">
                                @else
                                    {{ $item->actual_duration ? format_duration($item->actual_duration) : $item->actual_duration }}
                                @endif
                                </td>

                            <td class="status-field">
                                @if($canUpdate)
                                @php
                                    $sla_status_arr = config('sla_status.names');
                                @endphp
                                <select class="form-select inline-edit" data-id="{{ $item->id }}" data-field="sla_status">
                                    @foreach($sla_status_arr as $key => $status)
                                        <option value="{{ $key }}"
                                            @if($item->sla_status == $key) selected @endif
                                        >
                                            {{ $status }}
                                        </option>
                                    @endforeach
                                </select>
                                @else
                                    <!-- <span class="{{ config('sla_status.badge')[$item->sla_status] }}">{{ isset(config('sla_status.names')[$item->sla_status]) ? config('sla_status.names')[$item->sla_status] : $item->sla_status }}</span> -->
                                    <span class="{{ data_get(config('sla_status.badge'), $item->sla_status, 'badge badge-default') }}">
                                        {{ data_get(config('sla_status.names'), $item->sla_status, $item->sla_status) }}
                                    </span>
                                @endif
                            </td>
                    

                            <td>
                                @if($canUpdate)
                                <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="delay_reason"
                                    value="{{ $item->delay_reason }}">
                                @else
                                    {{ $item->delay_reason }}
                                @endif
                            </td>

                            <td>
                                @if($canUpdate)
                                <input class="form-control inline-edit outsourced-provider" data-id="{{ $item->id }}" data-field="outsourced_provider"
                                    value="{{ $item->outsourced_provider }}">
                                @else
                                    {{ $item->outsourced_provider }}
                                @endif
                            </td>

                            <td>
                                @if($canUpdate)
                                <input class="form-control inline-edit" data-id="{{ $item->id }}" data-field="acceptance_result"
                                    value="{{ $item->acceptance_result }}">
                                @else
                                    {{ $item->acceptance_result }}
                                @endif
                            </td>

                            <td class="confirm_checked">
                                @can('confirm maintenance')
                                    @if ($item->sla_status == config('sla_status.code.CONFIRMED'))
                                    <input
                                        type="checkbox"
                                        class="confirm-request"
                                        data-id="{{ $item->id }}"
                                        {{ $item->is_confirmed ? 'checked' : '' }}>
                                    @endif
                                @else
                                    @if($item->is_confirmed)
                                        <span class="badge bg-success">
                                            Đã xác nhận
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            Chưa xác nhận
                                        </span>
                                    @endif
                                @endcan
                            </td>

                            <td class="confirmer-name">
                                @if($canUpdate)
                                <input class="form-control inline-edit" data-id="{{ $item->id }}"
                                    data-field="acceptance_confirmed_by" value="{{ $item->acceptance_confirmed_by }}">
                                @else
                                    {{ $item->acceptance_confirmed_by }}
                                @endif
                            </td>

                            <td>
                                <div>
                                    Đã nhắc:
                                    {{ $item->reminder_count }}
                                </div>
                            </td>
                            <td>{{ $item->created_at?->format('d/m/Y H:i') }}</td>
                            <td>{{ $item->updated_at?->format('d/m/Y H:i') }}</td>
                            <td class="action-column">
                                <div class="dropdown">
                                    <button
                                        class="btn btn-sm btn-primary dropdown-toggle"
                                        type="button"
                                        data-bs-toggle="dropdown">
                                        Thao tác
                                    </button>
                                    <ul class="dropdown-menu">
                                        @hasanyrole('technician|admin')
                                        @if($item->sla_status == config('sla_status.code.NEW'))
                                            <li>
                                                <a class="dropdown-item change-status-btn"
                                                href=""
                                                data-id="{{ $item->id }}"
                                                data-status="{{ config('sla_status.code.PROCESSING') }}">
                                                    Tiếp nhận
                                                </a>
                                            </li>
                                        @elseif(in_array($item->sla_status,[config('sla_status.code.PROCESSING'),config('sla_status.code.CONTINUE_PROCESSING')]))
                                            <li>
                                                <a class="dropdown-item change-status-btn"
                                                href=""
                                                data-id="{{ $item->id }}"
                                                data-status="{{ config('sla_status.code.WAITING_CONFIRM') }}">
                                                    Hoàn thành
                                                </a>
                                            </li>
                                            @endif
                                        @endhasanyrole

                                        @hasanyrole('manager|admin')
                                        @if($item->sla_status == config('sla_status.code.WAITING_CONFIRM'))
                                            <li>
                                                <a class="dropdown-item change-status-btn"
                                                href=""
                                                data-id="{{ $item->id }}"
                                                data-status="{{ config('sla_status.code.CONFIRMED') }}">
                                                    Duyệt
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item text-danger change-status-btn"
                                                href=""
                                                data-id="{{ $item->id }}"
                                                data-status="{{ config('sla_status.code.REJECTED') }}">
                                                    {{ config('sla_status.names.REJECTED') }}
                                                </a>
                                            </li>
                                        @elseif($item->sla_status == config('sla_status.code.REJECTED'))
                                            <li>
                                                <a class="dropdown-item text-danger change-status-btn"
                                                href=""
                                                data-id="{{ $item->id }}"
                                                data-status="{{ config('sla_status.code.REOPEN') }}">
                                                    {{ config('sla_status.names.REOPEN') }}
                                                </a>
                                            </li>
                                        @endif
                                        @endhasanyrole
                                        @can('remind maintenance')
                                        @if($item->sla_status == config('sla_status.code.PROCESSING'))
                                        <li>
                                            <a class="dropdown-item btn-remind"
                                            href=""
                                            data-id="{{ $item->id }}">
                                                Gửi nhắc việc
                                            </a>
                                        </li>
                                        @endif
                                        @endcan
                                        @role('admin')
                                        @if (in_array($item->sla_status,[config('sla_status.code.NEW'),config('sla_status.code.PROCESSING')] ))
                                        <li>
                                            <a class="dropdown-item text-warning change-status-btn"
                                            href=""
                                            data-id="{{ $item->id }}"
                                            data-status="{{ config('sla_status.code.PENDING') }}">
                                                {{ config('sla_status.names.PENDING') }}
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-warning change-status-btn"
                                            href=""
                                            data-id="{{ $item->id }}"
                                            data-status="{{ config('sla_status.code.PENDING_CONTRACTOR') }}">
                                                {{ config('sla_status.names.PENDING_CONTRACTOR') }}
                                            </a>
                                        </li>
                                        @endif
                                        @if ($item->sla_status == config('sla_status.code.PENDING'))
                                        <li>
                                            <a class="dropdown-item change-status-btn"
                                            href=""
                                            data-id="{{ $item->id }}"
                                            data-status="{{ config('sla_status.code.CONTINUE_PROCESSING') }}">
                                            {{ config('sla_status.names.CONTINUE_PROCESSING') }}
                                            </a>
                                        </li>
                                        @endif
                                        <li>
                                            <a class="dropdown-item view-log-btn"
                                                href="#"
                                                data-id="{{ $item->id }}">
                                                Lịch sử trạng thái
                                            </a>
                                        </li>
                                        @endrole
                                        {{-- Delete --}}
                                        @can('delete data')
                                        @if($item->sla_status == config('sla_status.code.NEW'))
                                            <li>
                                                <form
                                                    action="{{ route('maintenance-requests.destroy', $item->id) }}"
                                                    method="POST"
                                                    onsubmit="return confirm('Xóa bản ghi này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button
                                                        type="submit"
                                                        class="dropdown-item text-danger">
                                                        Xóa
                                                    </button>
                                                </form>
                                            </li>
                                        @endif
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    @endif
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $requests->links() }}
        </div>
    </div>