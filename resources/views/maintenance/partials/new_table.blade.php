@if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="py-4">
    <div class="table-scroll-top">
        <div></div>
    </div>
    <div class="table-responsive">
        <table id="tbData" class="table table-bordered table-striped">
            <thead>
                <tr>
                    @foreach (config('maintenance.tbl_fields') as $key_field => $fields)
                        <th class="{{ in_array($key_field, ['severity','status']) ? $key_field.'_field' : '' }}">{{ $fields }}</th>
                    @endforeach
                    <!-- <th>Nhắc việc</th> -->
                    <th class="action-column">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @isset($requests)
                @foreach($requests as $item)
                    @php
                        // Đặt lên đầu foreach để tận dụng biến chung
                        $severities = $severities ?? collect(config('severities'))->keyBy('key');
                        $acceptanceList = $acceptanceList ?? collect(config('acceptance'))->pluck('name', 'key');
                        $sla = $realTimeMap[$item->standard_completion_time] ?? null;
                        $isOverdue = false;
                        if ($sla && !empty($item->request_date)) {
                            $createdAt = \Carbon\Carbon::parse($item->request_date);
                            $elapsedSeconds = $createdAt->diffInSeconds(now());
                            $isOverdue = $elapsedSeconds > (int) ($sla['max_seconds'] ?? 0)
                                && !in_array($item->sla_status, [config('sla_status.code.COMPLETED'), config('sla_status.code.LATED')]);
                        }
                        $severityName = $severities[$item->severity]['name'] ?? $item->severity;
                        $slaStatusBadge = data_get(config('sla_status.badge'), $item->sla_status, 'badge badge-default');
                        $slaStatusName = data_get(config('sla_status.names'), $item->sla_status, $item->sla_status);
                        $timeName = $item->standard_completion_time
                            ? ($realTimeMap[$item->standard_completion_time]['name'] ?? $item->standard_completion_time)
                            : '';
                    @endphp
                    <tr data-id="{{ $item->id }}" class="{{ $isOverdue ? 'table-danger' : '' }}">
                        <td>{{ $item->id }}</td>
                        <td class="branch_data">
                            Mã: {{ $item->branch_code }} <br>
                            Tên: {{ $item->branch_name }}
                        </td>
                        <td class="item_category_class">
                            Tên: {{ $item->item_category }} <br>
                            Loại sự cố: {{ $severityName }} <br>
                            <!-- Trạng thái:
                            <span class="{{ $slaStatusBadge }}">
                                {{ $slaStatusName }}
                            </span> -->
                        </td>
                        <td class="textarea-field">{{ $item->issue_description }}</td>
                        <!-- <td class="textarea-field">{{ $item->solution_description }}</td> -->
                        <!-- <td class="tech_data">
                            Tên: {{ $item->technician_name }}<br>
                            SĐT: {{ $item->technician_mobile }}<br>
                            Email: {{ $item->technician_email }}
                        </td> -->
                        <!-- <td>
                            @if($item->include_saturday)
                                <span class="badge bg-primary">
                                    T7
                                </span>
                            @endif

                            @if($item->include_sunday)
                                <span class="badge bg-info">
                                    CN
                                </span>
                            @endif

                            @if($item->include_holiday)
                                <span class="badge bg-warning">
                                    Lễ
                                </span>
                            @endif

                            @if(
                                !$item->include_saturday
                                && !$item->include_sunday
                                && !$item->include_holiday
                            )
                                <span class="badge bg-secondary">
                                    Hành chính
                                </span>
                            @endif

                        </td> -->
                        <td class="time_field">
                            Ngày yêu cầu: {{ $item->request_date ? \Carbon\Carbon::parse($item->request_date)->format('d/m/Y H:i:s') : '' }}<br>
                            Yêu cầu hoàn thành: {{ $timeName }}
                            @if($item->pending_at)
                                <br>Ngày tạm dừng: {{ \Carbon\Carbon::parse($item->pending_at)->format('d/m/Y H:i:s') }}
                            @endif
                            @if($item->processing_at)
                                <br>Ngày tiếp tục: {{ \Carbon\Carbon::parse($item->processing_at)->format('d/m/Y H:i:s') }}
                            @endif
                            @if($item->actual_completion_date)
                                <br>Ngày hoàn thành: {{ \Carbon\Carbon::parse($item->actual_completion_date)->format('d/m/Y H:i:s') }}
                            @endif
                            @if($item->actual_duration)
                                <br>Thời gian thực tế: {{ format_duration($item->actual_duration) }}
                            @endif
                            <hr>
                            Tạo: {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i:s') }}<br>
                            Cập nhật: {{ \Carbon\Carbon::parse($item->updated_at)->format('d/m/Y H:i:s') }}
                        </td>
                        <!-- <td>{{ $item->delay_reason }}</td> -->
                        <td class="confirm_checked status-field">
                            <span class="status_badge {{ $slaStatusBadge }}">
                                {{ $slaStatusName }}
                            </span><br>
                            <span class="badge {{ $item->is_confirmed ? 'bg-success' : 'bg-secondary' }}">
                                {{ $item->is_confirmed ? 'Xác nhận nghiệm thu' : 'Chưa xác nhận nghiệm thu' }}
                            </span>
                        </td>
                        <!-- <td>{{ $item->outsourced_provider }}</td> -->
                        <td>{{ $acceptanceList[$item->acceptance_result] ?? $item->acceptance_result }}</td>
                        <td class="confirmer-name">{{ $item->acceptance_confirmed_by }}</td>
                        <!-- <td>
                            Đã nhắc: {{ $item->reminder_count }}
                            {!! $item->last_reminded_at ? '<br>Nhắc lần cuối: '.\Carbon\Carbon::parse($item->last_reminded_at)->format('d/m/Y H:i:s') : '' !!}
                        </td> -->
                        <td class="action-column">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">Thao tác</button>
                                <ul class="dropdown-menu">
                                    <li>
                                        <a
                                            role="button"
                                            class="dropdown-item btn-detail"
                                            data-id="{{ $item->id }}"
                                        >
                                            Chi tiết
                                        </a>
                                    </li>
                                    @hasanyrole('technician|admin')
                                        @if(in_array($item->sla_status, [config('sla_status.code.NEW'), config('sla_status.code.REOPEN')]))
                                            <li>
                                                <a class="dropdown-item change-status-btn"
                                                    href=""
                                                    data-id="{{ $item->id }}"
                                                    data-status="{{ config('sla_status.code.PROCESSING') }}">
                                                    {{ $item->sla_status == config('sla_status.code.NEW') ? 'Tiếp nhận' : 'Xử lý lại' }}
                                                </a>
                                            </li>
                                        @elseif(in_array($item->sla_status, [config('sla_status.code.PROCESSING'), config('sla_status.code.CONTINUE_PROCESSING')]))
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
                                            <li>
                                                <a class="dropdown-item change-status-btn"
                                                    href=""
                                                    data-id="{{ $item->id }}"
                                                    data-status="{{ config('sla_status.code.WAITING_CONFIRM') }}">
                                                    Hoàn thành Y/C
                                                </a>
                                            </li>
                                        @elseif($item->sla_status == config('sla_status.code.PENDING_CONTRACTOR'))
                                            <li>
                                                <a class="dropdown-item change-status-btn"
                                                    href=""
                                                    data-id="{{ $item->id }}"
                                                    data-status="{{ config('sla_status.code.WAITING_CONFIRM') }}">
                                                    Hoàn thành Y/C
                                                </a>
                                            </li>
                                        @elseif($item->sla_status == config('sla_status.code.PENDING'))
                                            <li>
                                                <a class="dropdown-item change-status-btn"
                                                    href=""
                                                    data-id="{{ $item->id }}"
                                                    data-status="{{ config('sla_status.code.CONTINUE_PROCESSING') }}">
                                                    Tiếp tục xử lý
                                                </a>
                                            </li>
                                        @endif
                                    @endhasanyrole
                                    @hasanyrole('manager|admin')
                                        @if(in_array($item->sla_status,[config('sla_status.code.WAITING_CONFIRM')]))
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
                                                    Từ chối Y/C
                                                </a>
                                            </li>
                                        @elseif($item->sla_status == config('sla_status.code.REJECTED'))
                                            <li>
                                                <a class="dropdown-item text-danger change-status-btn"
                                                    href=""
                                                    data-id="{{ $item->id }}"
                                                    data-status="{{ config('sla_status.code.REOPEN') }}">
                                                    Y/C xử lý lại
                                                </a>
                                            </li>
                                        @endif
                                    @endhasanyrole
                                    @can('confirm maintenance')
                                        @if(in_array($item->sla_status,[
                                                    config('sla_status.code.COMPLETED'),
                                                    config('sla_status.code.LATED')
                                                ])
                                            && empty($item->acceptance_result)
                                        )
                                        <li>
                                            <a href="#"
                                               class="dropdown-item acceptance-btn"
                                               data-bs-toggle="modal"
                                               data-bs-target="#acceptanceModal"
                                               data-id="{{ $item->id }}">
                                                Nghiệm thu
                                            </a>
                                        </li>
                                        @endif
                                    @endcan
                                    @can('remind maintenance')
                                        @if(in_array($item->sla_status, [config('sla_status.code.PROCESSING'), config('sla_status.code.CONTINUE_PROCESSING')]))
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
                                        <li>
                                            <a href="#"
                                               class="dropdown-item admin-change-status-btn"
                                               data-id="{{ $item->id }}"
                                               data-current-status="{{ $item->sla_status }}">
                                                Đổi trạng thái
                                            </a>
                                        </li>
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
                                                <form action="{{ route('maintenance-requests.destroy', $item->id) }}"
                                                      method="POST"
                                                      onsubmit="return confirm('Xóa bản ghi này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">Xóa</button>
                                                </form>
                                            </li>
                                        @endif
                                    @endcan
                                </ul>
                            </div>
                        </td>
                    </tr>
                @endforeach
                @endisset
            </tbody>
        </table>
    </div>
    <div class="mt-3">
        {{ $requests->links() }}
    </div>
</div>