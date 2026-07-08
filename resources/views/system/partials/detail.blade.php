@php
    $config = config();
    $acceptanceList = collect($config['acceptance'] ?? [])->pluck('name', 'key');
    $realTimeMap = collect($config['real_time'] ?? [])->keyBy('key');
    $sla = $maintenanceSystem->standard_completion_time ? ($realTimeMap[$maintenanceSystem->standard_completion_time] ?? null) : null;

    $slaStatusBadge = data_get($config['sla_status']['badge'] ?? [], $maintenanceSystem->status, 'badge badge-default');
    $slaStatusName = data_get($config['sla_status']['names_ht'] ?? [], $maintenanceSystem->status, $maintenanceSystem->status);
    $timeName = $maintenanceSystem->standard_completion_time
        ? ($realTimeMap[$maintenanceSystem->standard_completion_time]['name'] ?? $maintenanceSystem->standard_completion_time)
        : '';
@endphp

<ul class="nav nav-tabs mb-2" id="maintenanceTab" role="tablist">
    @foreach([
        ['id' => 'info', 'label' => 'Thông tin chung', 'active' => true],
        ['id' => 'history', 'label' => 'Lịch sử'],
    ] as $tab)
        <li class="nav-item" role="presentation">
            <button class="nav-link{{ !empty($tab['active']) ? ' active' : '' }}"
                id="{{ $tab['id'] }}-tab"
                data-bs-toggle="tab"
                data-bs-target="#{{ $tab['id'] }}-tab-pane"
                type="button"
                role="tab"
                aria-controls="{{ $tab['id'] }}-tab-pane"
                aria-selected="{{ !empty($tab['active']) ? 'true' : 'false' }}">
                {{ $tab['label'] }}
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content" id="maintenanceTabContent">
    {{-- Thông tin chung --}}
    <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" aria-labelledby="info-tab">
        <div class="d-flex flex-wrap gap-2">

            {{-- Card Sự cố --}}
            <div class="card flex-fill" style="min-width:240px;max-width:360px">
                <div class="card-header bg-light fw-semibold py-2 small">Sự cố</div>
                <div class="card-body pb-1 px-2 small d-flex flex-column gap-1">
                    <div>
                        <span class="fw-bold">ID: </span>{{ $maintenanceSystem->id }}
                    </div>
                    <div>
                        <span class="fw-bold">Cơ sở: </span>{{ $maintenanceSystem->branch_code }} - {{ $maintenanceSystem->branch_name }}
                    </div>
                    <div>
                        <span class="fw-bold">Tình trạng: </span>
                        <span class="{{ $slaStatusBadge }}">{{ $slaStatusName }}</span>
                    </div>
                    <div>
                        <span class="fw-bold">Nghiệm thu: </span>
                        <span class="badge {{ $maintenanceSystem->is_confirmed ? 'bg-success' : 'bg-secondary' }}">
                            {{ $maintenanceSystem->is_confirmed ? 'Đã nghiệm thu' : 'Chưa nghiệm thu' }}
                        </span>
                    </div>
                    <div>
                        <span class="fw-bold">KQ nghiệm thu: </span>
                        {{ $acceptanceList[$maintenanceSystem->acceptance_result] ?? $maintenanceSystem->acceptance_result }}
                    </div>
                    <div>
                        <span class="fw-bold">Người xác nhận: </span>{{ $maintenanceSystem->acceptance_confirmed_by }}
                    </div>
                </div>
            </div>

            {{-- Card Mô tả --}}
            <div class="card flex-fill" style="min-width:240px;max-width:360px">
                <div class="card-header bg-light fw-semibold py-2 small">Mô tả</div>
                <div class="card-body pb-1 px-2 small d-flex flex-column gap-1">
                    @foreach([
                        ['label' => 'Sự cố', 'field' => 'issue_name'],
                        ['label' => 'Mô tả', 'field' => 'issue_description'],
                        ['label' => 'Giải pháp', 'field' => 'solution_description'],
                        ['label' => 'Lý do trễ', 'field' => 'delay_reason'],
                    ] as $desc)
                        <div>
                            <span class="fw-bold">{{ $desc['label'] }}: </span>
                            {!! nl2br(e($maintenanceSystem->{$desc['field']})) !!}
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Card Thời gian & Tiến trình --}}
            <div class="card flex-fill" style="min-width:240px;max-width:360px">
                <div class="card-header bg-light fw-semibold py-2 small">Thời gian & Tiến trình</div>
                <div class="card-body pb-1 px-2 small d-flex flex-column gap-1">
                    <div>
                        <span class="fw-bold">Ngày làm: </span>
                        <span class="badge bg-secondary me-1">Hành chính</span>
                        @foreach([
                            ['cond' => 'include_saturday', 'class' => 'bg-primary', 'text' => 'T7'],
                            ['cond' => 'include_sunday', 'class' => 'bg-info', 'text' => 'CN'],
                            ['cond' => 'include_holiday', 'class' => 'bg-warning', 'text' => 'Lễ'],
                        ] as $day)
                            @if($maintenanceSystem->{$day['cond']})
                                <span class="badge {{ $day['class'] }} me-1">{{ $day['text'] }}</span>
                            @endif
                        @endforeach
                    </div>
                    <div>
                        <span class="fw-bold">Hạn: </span>{{ $timeName }}
                    </div>
                    <div>
                        <span class="fw-bold">YC: </span>
                        {{ $maintenanceSystem->request_date ? \Carbon\Carbon::parse($maintenanceSystem->request_date)->format('d/m/Y H:i') : '' }}
                    </div>
                    @foreach([
                        ['field' => 'pending_at', 'label' => 'Tạm dừng'],
                        ['field' => 'processing_at', 'label' => 'Tiếp tục'],
                    ] as $time)
                        @if($maintenanceSystem->{$time['field']})
                            <div>
                                <span class="fw-bold">{{ $time['label'] }}: </span>
                                {{ \Carbon\Carbon::parse($maintenanceSystem->{$time['field']})->format('d/m/Y H:i') }}
                            </div>
                        @endif
                    @endforeach
                    <div>
                        <span class="fw-bold">HT: </span>
                        {{ $maintenanceSystem->actual_completion_date ? \Carbon\Carbon::parse($maintenanceSystem->actual_completion_date)->format('d/m/Y H:i') : '' }}
                    </div>
                    <div>
                        <span class="fw-bold">TG thực tế: </span>
                        @php
                            $duration = $maintenanceSystem->actual_duration;
                        @endphp
                        {{ ($duration && preg_match('/^\d{2}:\d{2}:\d{2}$/', $duration)) ? format_duration($duration) : $duration }}
                    </div>
                    <div>
                        <span class="fw-bold">Tạo: </span>
                        {{ \Carbon\Carbon::parse($maintenanceSystem->created_at)->format('d/m/Y H:i') }}
                    </div>
                    <div>
                        <span class="fw-bold">Cập nhật: </span>
                        {{ \Carbon\Carbon::parse($maintenanceSystem->updated_at)->format('d/m/Y H:i') }}
                    </div>
                </div>
            </div>

            {{-- Card Kỹ thuật viên --}}
            <div class="card flex-fill" style="min-width:240px;max-width:360px">
                <div class="card-header bg-light fw-semibold py-2 small">Kỹ thuật viên</div>
                <div class="card-body pb-1 px-2 small d-flex flex-column gap-1">
                    @foreach([
                        ['label' => 'Tên', 'field' => 'technician_name'],
                        ['label' => 'SĐT', 'field' => 'technician_mobile'],
                        ['label' => 'Email', 'field' => 'technician_email'],
                    ] as $tech)
                        <div>
                            <span class="fw-bold">{{ $tech['label'] }}: </span>
                            {{ $maintenanceSystem->{$tech['field']} }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Lịch sử --}}
    <div class="tab-pane fade" id="history-tab-pane" role="tabpanel" aria-labelledby="history-tab">
        <div class="card border-0">
            <div class="card-header fw-semibold py-2 small">Lịch sử</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0 align-middle text-center small">
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
                                    default => 'dark',
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
</div>