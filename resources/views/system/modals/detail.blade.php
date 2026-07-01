@php
    $status = $maintenanceSystem->status;
    $statusName = config('sla_status.names.' . $status) ?? $status;
    $statusBadge = config('sla_status.badge.' . $status) ?? 'bg-secondary';
    $slaName = $maintenanceSystem->standard_completion_time
        ? str_replace('_', ' ', $maintenanceSystem->standard_completion_time)
        : '-';
@endphp

<div>
    <ul class="nav nav-tabs mb-3" id="maintenanceDetailTab" role="tablist">
        <li class="nav-item">
            <button class="nav-link active" id="tab-information-tab" data-bs-toggle="tab"
                data-bs-target="#tab-information" type="button" role="tab">
                Thông tin chung
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" id="tab-history-tab" data-bs-toggle="tab" data-bs-target="#tab-history"
                type="button" role="tab">
                Lịch sử xử lý
                <span class="badge bg-secondary">{{ $maintenanceSystem->logs->count() }}</span>
            </button>
        </li>
    </ul>
    <div class="tab-content">
        {{-- TAB 1: Thông tin chung --}}
        <div class="tab-pane fade show active" id="tab-information" role="tabpanel">
            <div class="row">
                {{-- Col 1: Sự cố / Dịch vụ --}}
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-bold">Thông tin sự cố / Dịch vụ</div>
                        <div class="card-body">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <th width="130">Mã lỗi</th>
                                    <td>{{ $maintenanceSystem->issue_code ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Tên sự cố</th>
                                    <td>{{ $maintenanceSystem->issue_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Trạng thái</th>
                                    <td>
                                        <span class="badge {{ $statusBadge }}">
                                            {{ $statusName }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Đã xác nhận</th>
                                    <td>
                                        @if($maintenanceSystem->is_confirmed)
                                            <span class="badge bg-success">Đã xác nhận</span>
                                        @else
                                            <span class="badge bg-danger">Chưa</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>SLA chuẩn</th>
                                    <td>{{ $slaName }}</td>
                                </tr>
                                <tr>
                                    <th>Thực hiện thực tế</th>
                                    <td>{{ $maintenanceSystem->actual_duration ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                {{-- Col 2: Chi nhánh / KTV --}}
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-light fw-bold">Chi nhánh & Kỹ thuật viên</div>
                        <div class="card-body">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <th width="130">Mã chi nhánh</th>
                                    <td>{{ $maintenanceSystem->branch_code ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Tên chi nhánh</th>
                                    <td>{{ $maintenanceSystem->branch_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Email chi nhánh</th>
                                    <td>{{ $maintenanceSystem->branch_email ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Kỹ thuật viên</th>
                                    <td>{{ $maintenanceSystem->technician_name ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Email KTV</th>
                                    <td>{{ $maintenanceSystem->technician_email ?: '-' }}</td>
                                </tr>
                                <tr>
                                    <th>Điện thoại KTV</th>
                                    <td>{{ $maintenanceSystem->technician_mobile ?: '-' }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                {{-- Col 3: Nội dung mô tả --}}
                <div class="col-md-12 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light fw-bold">Nội dung</div>
                        <div class="card-body row">
                            <div class="col-md-4 mb-3">
                                <strong>Mô tả sự cố</strong>
                                <div class="border rounded p-3 mt-2 bg-light" style="min-height:80px">
                                    {!! nl2br(e($maintenanceSystem->issue_description ?: '-')) !!}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <strong>Mô tả khắc phục</strong>
                                <div class="border rounded p-3 mt-2 bg-light" style="min-height:80px">
                                    {!! nl2br(e($maintenanceSystem->solution_description ?: '-')) !!}</div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <strong>Lý do trễ</strong>
                                <div class="border rounded p-3 mt-2 bg-light" style="min-height:80px">
                                    {!! nl2br(e($maintenanceSystem->delay_reason ?: '-')) !!}</div>
                            </div>
                        </div>
                    </div>
                </div>
                {{-- Col 4: Thời gian --}}
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light fw-bold">Thời gian & Người thao tác</div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-2">
                                    <table class="table table-bordered table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <th width="180">Ngày yêu cầu</th>
                                                <td>{{ optional($maintenanceSystem->request_date)->format('d/m/Y H:i') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Tạo lúc</th>
                                                <td>{{ optional($maintenanceSystem->created_at)->format('d/m/Y H:i:s') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Cập nhật lúc</th>
                                                <td>{{ optional($maintenanceSystem->updated_at)->format('d/m/Y H:i:s') }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Bắt đầu xử lý</th>
                                                <td>{{ optional($maintenanceSystem->processing_at)->format('d/m/Y H:i:s') ?: '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Chuyển chờ thực hiện</th>
                                                <td>{{ optional($maintenanceSystem->pending_at)->format('d/m/Y H:i:s') ?: '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Hoàn thành</th>
                                                <td>{{ optional($maintenanceSystem->completed_at)->format('d/m/Y H:i:s') ?: '-' }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <th>Thời gian nghiệm thu</th>
                                                <td>{{ optional($maintenanceSystem->confirmed_at)->format('d/m/Y H:i:s') ?: '-' }}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <table class="table table-bordered table-sm mb-0">
                                        <tbody>
                                            <tr>
                                                <th width="180">Người tạo</th>
                                                <td>{{ $maintenanceSystem->created_by ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Người cập nhật</th>
                                                <td>{{ $maintenanceSystem->updated_by ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Người hoàn thành</th>
                                                <td>{{ $maintenanceSystem->completed_by ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Kết quả nghiệm thu</th>
                                                <td>{{ $maintenanceSystem->acceptance_result ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Người xác nhận nghiệm thu</th>
                                                <td>{{ $maintenanceSystem->acceptance_confirmed_by ?: '-' }}</td>
                                            </tr>
                                            <tr>
                                                <th>Ghi chú nghiệm thu</th>
                                                <td>{!! nl2br(e($maintenanceSystem->acceptance_note ?: '-')) !!}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div> {{-- .row --}}
        </div>
        {{-- HISTORY LOG --}}
        <div class="tab-pane fade" id="tab-history" role="tabpanel">
            <div class="card shadow-sm">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span class="fw-bold">Lịch sử xử lý</span>
                    <span class="badge bg-secondary">{{ $maintenanceSystem->logs->count() }} bản ghi</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th width="150">Thời gian</th>
                                    <th width="160">Người thực hiện</th>
                                    <th width="140">Thao tác</th>
                                    <th width="250">Trạng thái</th>
                                    <th>Ghi chú</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($maintenanceSystem->logs as $log)
                                    @php
                                        $actionColor = match ($log->action) {
                                            'CREATE' => 'success',
                                            'UPDATE' => 'primary',
                                            'CHANGE_STATUS' => 'warning',
                                            'CHANGE_TECHNICIAN' => 'info',
                                            'CHANGE_SLA' => 'secondary',
                                            'COMPLETE' => 'success',
                                            'REOPEN' => 'danger',
                                            default => 'dark'
                                        };
                                        $actionName = config('maintenance_log.actions')[$log->action] ?? $log->action;
                                        $oldStatusName = config('sla_status.names')[$log->old_status] ?? $log->old_status;
                                        $newStatusName = config('sla_status.names')[$log->new_status] ?? $log->new_status;
                                    @endphp
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i:s') }}</td>
                                        <td>{{ $log->performed_by }}</td>
                                        <td>
                                            <span class="badge bg-{{ $actionColor }}">{{ $actionName }}</span>
                                        </td>
                                        <td>
                                            @if($log->old_status)
                                                <span class="badge bg-secondary">{{ $oldStatusName }}</span>
                                                <i class="bi bi-arrow-right mx-1"></i>
                                            @endif
                                            @if($log->new_status)
                                                <span class="badge bg-primary">{{ $newStatusName }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $log->note ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Chưa có lịch sử xử lý.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div> {{-- end-tab-history --}}
    </div>
</div>