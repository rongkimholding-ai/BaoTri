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

@php
    // Khởi tạo chung cho cả view tránh lặp lại trong mỗi foreach
    $severities = collect(config('severities'))->keyBy('key');
    $acceptanceList = collect(config('acceptance'))->pluck('name', 'key');
    $tblFields = config('maintenance.tbl_fields');
    $slaStatusBadgeList = config('sla_status.badge');
    $slaStatusNames = config('sla_status.names');
    $slaStatusCode = config('sla_status.code');
@endphp

<div class="card">
    <div class="table-scroll-top"><div></div></div>
    <div class="d-none d-md-block">
        <div class="table-responsive">
            <table id="tbData" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        @foreach ($tblFields as $key_field => $fields)
                            <th class="{{ in_array($key_field, ['severity','status']) ? $key_field.'_field' : '' }}">{{ $fields }}</th>
                        @endforeach
                        <!-- <th>Nhắc việc</th> -->
                        <th class="action-column">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @isset($requests)
                    @php $stt = ($requests->currentPage() - 1) * $requests->perPage(); @endphp
                    @foreach($requests as $item)
                        @php
                            // Tính toán dữ liệu sử dụng nhiều lần
                            $sla = $realTimeMap[$item->standard_completion_time] ?? null;
                            $isOverdue = $sla && !empty($item->request_date)
                                ? (\Carbon\Carbon::parse($item->request_date)->diffInSeconds(now()) > (int) ($sla['max_seconds'] ?? 0)
                                    && !in_array($item->sla_status, [$slaStatusCode['COMPLETED'], $slaStatusCode['LATED']]))
                                : false;
                            $severityName = $severities[$item->severity]['name'] ?? $item->severity;
                            $slaStatusBadge = data_get($slaStatusBadgeList, $item->sla_status, 'badge badge-default');
                            $slaStatusName = data_get($slaStatusNames, $item->sla_status, $item->sla_status);
                            $timeName = $item->standard_completion_time ? ($realTimeMap[$item->standard_completion_time]['name'] ?? $item->standard_completion_time) : '';
                            $detailRoute = route('maintenance-requests.show', $item->id);
                        @endphp
                        <tr data-id="{{ $item->id }}" class="{{ $isOverdue ? 'table-danger' : '' }} tr-row-link" data-detail-url="{{ $detailRoute }}">
                            <td>{{ ++$stt }}</td>
                            <td class="textarea-field">
                                Mã: {{ $item->branch_code }} <br>
                                Tên: <b>{{ $item->branch_name }}</b>
                                <hr>
                                Hạng mục: <b>{{ $item->item_category }}</b> <br>
                                Loại sự cố: <b>{{ $severityName }}</b>
                                <hr>
                                KTV: {{ $item->technician_name }}{{ $item->technician_mobile ? ' ('.$item->technician_mobile.')': '' }}<br>
                                Email: {{ $item->technician_email }}
                            </td>
                            <!-- <td class="item_category_class">
                                Tên: {{ $item->item_category }} <br>
                                Loại sự cố: {{ $severityName }} <br>
                                Trạng thái: <span class="{{ $slaStatusBadge }}">
                                    {{ $slaStatusName }}
                                </span>
                            </td> -->
                            <td class="textarea-field">{{ $item->issue_description }}</td>
                            <!-- <td class="textarea-field">{{ $item->solution_description }}</td> -->
                            <!-- <td class="tech_data">
                                Tên: {{ $item->technician_name }}<br>
                                SĐT: {{ $item->technician_mobile }}<br>
                                Email: {{ $item->technician_email }}
                            </td> -->
                            <!-- <td>
                                @if($item->include_saturday)
                                    <span class="badge bg-primary">T7</span>
                                @endif
                                @if($item->include_sunday)
                                    <span class="badge bg-info">CN</span>
                                @endif
                                @if($item->include_holiday)
                                    <span class="badge bg-warning">Lễ</span>
                                @endif
                                @if(
                                    !$item->include_saturday
                                    && !$item->include_sunday
                                    && !$item->include_holiday
                                )
                                    <span class="badge bg-secondary">Hành chính</span>
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
                            <td class="text-center">{{ $acceptanceList[$item->acceptance_result] ?? $item->acceptance_result }}</td>
                            <td class="confirmer-name">{{ $item->acceptance_confirmed_by }}</td>
                            <!-- <td>
                                Đã nhắc: {{ $item->reminder_count }}
                                {!! $item->last_reminded_at ? '<br>Nhắc lần cuối: '.\Carbon\Carbon::parse($item->last_reminded_at)->format('d/m/Y H:i:s') : '' !!}
                            </td> -->
                            <td class="action-column action-cell">
                                <div class="dropdown">
                                    <button class="btn btn-primary dropdown-toggle action-btn" type="button" data-bs-toggle="dropdown">Thao tác</button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <a href="{{ $detailRoute }}" class="dropdown-item">
                                                Chi tiết
                                            </a>
                                        </li>
                                        @can('change-maintenance-status')
                                            @hasanyrole('technician|admin')
                                                @if(in_array($item->sla_status, [$slaStatusCode['NEW'], $slaStatusCode['REOPEN']]))
                                                    <li>
                                                        <a class="dropdown-item change-status-btn" href="#" data-id="{{ $item->id }}" data-status="{{ $slaStatusCode['PROCESSING'] }}">
                                                            {{ $item->sla_status == $slaStatusCode['NEW'] ? 'Tiếp nhận' : 'Xử lý lại' }}
                                                        </a>
                                                    </li>
                                                @elseif(in_array($item->sla_status, [$slaStatusCode['PROCESSING'], $slaStatusCode['CONTINUE_PROCESSING']]))
                                                    <li>
                                                        <a class="dropdown-item text-warning change-status-btn" href="#" data-id="{{ $item->id }}" data-status="{{ $slaStatusCode['PENDING'] }}">
                                                            {{ $slaStatusNames['PENDING'] }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item text-warning change-status-btn" href="#" data-id="{{ $item->id }}" data-status="{{ $slaStatusCode['PENDING_CONTRACTOR'] }}">
                                                            {{ $slaStatusNames['PENDING_CONTRACTOR'] }}
                                                        </a>
                                                    </li>
                                                    <li>
                                                        <a class="dropdown-item change-status-btn" href="#" data-id="{{ $item->id }}" data-status="{{ $slaStatusCode['WAITING_CONFIRM'] }}">
                                                            Hoàn thành Y/C
                                                        </a>
                                                    </li>
                                                @elseif($item->sla_status == $slaStatusCode['PENDING_CONTRACTOR'])
                                                    <li>
                                                        <a class="dropdown-item change-status-btn" href="#" data-id="{{ $item->id }}" data-status="{{ $slaStatusCode['WAITING_CONFIRM'] }}">
                                                            Hoàn thành Y/C
                                                        </a>
                                                    </li>
                                                @endif
                                            @endhasanyrole
                                            @hasanyrole('muasam|admin')
                                                @if($item->sla_status == $slaStatusCode['PENDING'])
                                                <li>
                                                    <a class="dropdown-item change-status-btn" href="#" data-id="{{ $item->id }}" data-status="{{ $slaStatusCode['CONTINUE_PROCESSING'] }}">
                                                        Tiếp tục xử lý
                                                    </a>
                                                </li>
                                                @endif
                                            @endhasanyrole
                                            @hasanyrole('am|om|admin')
                                            @if(in_array($item->sla_status,[$slaStatusCode['WAITING_CONFIRM']]))
                                                <li>
                                                    <a class="dropdown-item change-status-btn" href="#" data-id="{{ $item->id }}" data-status="{{ $slaStatusCode['CONFIRMED'] }}">
                                                        Duyệt
                                                    </a>
                                                </li>
                                                <li>
                                                    <a class="dropdown-item text-danger change-status-btn" href="#" data-id="{{ $item->id }}" data-status="{{ $slaStatusCode['REJECTED'] }}">
                                                        Từ chối Y/C
                                                    </a>
                                                </li>
                                            @elseif($item->sla_status == $slaStatusCode['REJECTED'])
                                                <li>
                                                    <a class="dropdown-item text-danger change-status-btn" href="#" data-id="{{ $item->id }}" data-status="{{ $slaStatusCode['REOPEN'] }}">
                                                        Y/C xử lý lại
                                                    </a>
                                                </li>
                                            @endif
                                            @endhasanyrole
                                        @endcan
                                        @can('confirm maintenance')
                                            @if(in_array($item->sla_status, [
                                                        $slaStatusCode['COMPLETED'],
                                                        $slaStatusCode['LATED']
                                                    ])
                                                && empty($item->acceptance_result)
                                            )
                                            <li>
                                                <a href="#" class="dropdown-item acceptance-btn"
                                                    data-bs-toggle="modal" data-bs-target="#acceptanceModal"
                                                    data-id="{{ $item->id }}">
                                                    Nghiệm thu
                                                </a>
                                            </li>
                                            @endif
                                        @endcan
                                        @can('remind maintenance')
                                            @if(in_array($item->sla_status, [$slaStatusCode['PROCESSING'], $slaStatusCode['CONTINUE_PROCESSING']]))
                                            <li>
                                                <a class="dropdown-item btn-remind" href="#" data-id="{{ $item->id }}">
                                                    Gửi nhắc việc
                                                </a>
                                            </li>
                                            @endif
                                        @endcan
                                        @role('admin')
                                        <li>
                                                <a href="javascript:void(0)"
                                                class="dropdown-item text-warning admin-update-tech-maintenance-btn"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateTechModal"
                                                data-action="{{ route('maintenance-requests.update-technician-info',['id'=>$item->id]) }}"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->technician_name }}"
                                                data-email="{{ $item->technician_email }}"
                                                data-mobile="{{ $item->technician_mobile }}">
                                                    <i class="bi bi-tools"></i> Cập nhật kỹ thuật viên
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" class="dropdown-item admin-change-status-btn" data-id="{{ $item->id }}" data-current-status="{{ $item->sla_status }}">
                                                    Đổi trạng thái
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item view-log-btn" href="#" data-id="{{ $item->id }}">
                                                    Lịch sử trạng thái
                                                </a>
                                            </li>
                                        @endrole
                                        {{-- Delete --}}
                                        @can('delete data')
                                        @hasrole('admin')
                                            @if($item->sla_status == $slaStatusCode['PROCESSING'])
                                                <li>
                                                    <form action="{{ route('maintenance-requests.destroy', $item->id) }}" method="POST"
                                                        onsubmit="return confirm('Xóa bản ghi này?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">Xóa</button>
                                                    </form>
                                                </li>
                                            @endif
                                        @endhasrole
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
    </div>
    <div class="d-block d-md-none">
        @isset($requests)
        @foreach($requests as $item)
            @php
                $sla = $realTimeMap[$item->standard_completion_time] ?? null;
                $severityName = $severities[$item->severity]['name'] ?? $item->severity;
                $slaStatusBadge = data_get($slaStatusBadgeList, $item->sla_status, 'bg-secondary');
                $slaStatusName = data_get($slaStatusNames, $item->sla_status, $item->sla_status);
                $isOverdue = $sla && !empty($item->request_date)
                    ? (\Carbon\Carbon::parse($item->request_date)->diffInSeconds(now()) > (int) ($sla['max_seconds'] ?? 0)
                        && !in_array($item->sla_status, [$slaStatusCode['COMPLETED'], $slaStatusCode['LATED']]))
                    : false;
                $timeName = $item->standard_completion_time ? ($realTimeMap[$item->standard_completion_time]['name'] ?? $item->standard_completion_time) : '';
            @endphp
            <div class="mobile-request-card mobile-row-link {{ $isOverdue ? 'mobile-danger' : '' }}" data-url="{{ route('maintenance-requests.show', $item->id) }}">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <div>{{ $item->branch_code }}</div>
                        <div class="fw-bold">{{ $item->branch_name }}</div>
                    </div>
                    <div class="status-group">
                        <span class="status_badge {{ $slaStatusBadge }}">
                            {{ $slaStatusName }}
                        </span>
                        <small class="text-muted d-block mt-1">
                            {!! $item->is_confirmed ? '&#x2705; Đã nghiệm thu' : '&#x23F3; Chờ nghiệm thu' !!}
                        </small>
                    </div>
                </div>
                <hr>
                <div><strong>Hạng mục:</strong> {{ $item->item_category }}</div>
                <div class="mt-2"><strong>Loại sự cố:</strong> {{ $severityName }}</div>
                <div class="mt-2"><strong>Mô tả:</strong> {{ $item->issue_description }}</div>
                <div class="mt-2">
                    <strong>Ngày yêu cầu:</strong> {{ $item->request_date ? \Carbon\Carbon::parse($item->request_date)->format('d/m/Y H:i:s') : '' }}<br>
                    <strong>Yêu cầu hoàn thành:</strong> {{ $timeName }}<br>
                    @if($item->actual_completion_date)
                        <strong>Ngày hoàn thành:</strong> {{ \Carbon\Carbon::parse($item->actual_completion_date)->format('d/m/Y H:i:s') }}<br>
                    @endif
                    @if($item->actual_duration)
                        <strong>Thời gian thực tế:</strong> {{ format_duration($item->actual_duration) }}
                    @endif
                </div>
                <div class="mt-3 d-grid">
                    <a href="{{ route('maintenance-requests.show', $item->id) }}" class="btn btn-primary">Chi tiết</a>
                </div>
            </div>
        @endforeach
        @endisset
    </div>
    <div class="mt-3">
        {{ $requests->links() }}
    </div>
</div>
<style>
.tr-row-link { cursor: pointer; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.tr-row-link').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button, .dropdown, .dropdown-menu')) return;
            const url = this.dataset.detailUrl;
            if (url) window.location.href = url;
        });
    });
    document.querySelectorAll('.mobile-row-link').forEach(function(card){
        card.addEventListener('click', function(e){
            if(e.target.closest('a,button')) return;
            window.location.href = this.dataset.url;
        });
    });
});
</script>