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
    $workType = config('work_type.name');
@endphp

<ul class="nav nav-tabs mb-2" id="maintenanceTab" role="tablist">
    @foreach([
        ['id' => 'info', 'label' => 'Thông tin chung', 'active' => true],
        ['id' => 'images', 'label' => 'Hình ảnh'],
        ['id' => 'history', 'label' => 'Lịch sử trạng thái'],
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
                    <div>
                        <span class="fw-bold">Hình thức: </span>
                        <span class="badge {{ $maintenanceSystem->work_type == 1 ? 'bg-dark' : 'bg-primary' }}">
                                            {{ $workType[$maintenanceSystem->work_type] ?? '-' }}
                                        </span>
                    </div>
                    @if ($maintenanceSystem->gps_at && $maintenanceSystem->distance)
                    <hr>
                    <div>
                        <span class="fw-bold">Thời gian lấy GPS: </span>
                        {{ $maintenanceSystem->gps_at ? \Carbon\Carbon::parse($maintenanceSystem->gps_at)->format('d/m/Y H:i') : '-'  }}
                    </div>
                    <div>
                        <span class="fw-bold">{{ $maintenanceSystem->branch_name }}: </span> 
                        (Khoảng cách {{ number_format($maintenanceSystem->distance, 2) }} m)
                        
                    </div>
                    @endif
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

    {{-- Hình ảnh --}}
    <div class="tab-pane fade" id="images-tab-pane" role="tabpanel" aria-labelledby="images-tab">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold py-2">
                Hình ảnh ({{ $maintenanceSystem->images->count() }})
            </div>
            <div class="card-body">
                {{-- Images --}}
                <div class="row g-3 mb-3">
                    @forelse($maintenanceSystem->images as $image)
                        <div class="col-6 col-md-4 col-lg-3 img-item">
                            <div class="card h-100 border shadow-sm image-card">
                                <div class="position-relative">
                                    <a href="{{ Storage::url($image->path) }}" target="_blank">
                                        <img src="{{ Storage::url($image->path) }}" class="card-img-top" style="height:180px; object-fit:cover;">
                                    </a>
                                    @role('admin')
                                        <button type="button" class="btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 m-2 delete-image" data-id="{{ $image->id }}" title="Xóa ảnh">X</button>
                                    @endrole
                                </div>
                                <div class="card-footer bg-white py-2 text-center">
                                    <a href="{{ Storage::url($image->path) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">Xem ảnh</a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-light border text-center mb-0">Chưa có hình ảnh đính kèm</div>
                        </div>
                    @endforelse
                </div>

                {{-- Attachments --}}
                <div class="mt-4">
                    <div class="card-header fw-semibold py-2 bg-white px-0" style="border-bottom:1px solid #eee;">
                        File/Tài liệu đính kèm ({{ $maintenanceSystem->attachments->count() ?? 0 }})
                    </div>
                    <div class="row g-3 mt-2">
                        @forelse($maintenanceSystem->attachments as $file)
                            <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                <div class="card h-100 border shadow-sm">
                                    <div class="card-body text-center d-flex flex-column justify-content-center align-items-center p-3">
                                        @php
                                            $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
                                            $icon = 'bi-file-earmark';
                                            if (in_array($ext, ['pdf'])) $icon = 'bi-file-earmark-pdf text-danger';
                                            elseif (in_array($ext, ['doc','docx'])) $icon = 'bi-file-earmark-word text-primary';
                                            elseif (in_array($ext, ['xls','xlsx'])) $icon = 'bi-file-earmark-excel text-success';
                                            elseif (in_array($ext, ['ppt','pptx'])) $icon = 'bi-file-earmark-ppt text-warning';
                                            elseif (in_array($ext, ['jpg','jpeg','png'])) $icon = 'bi-file-earmark-image text-info';
                                            elseif (in_array($ext, ['mp4'])) $icon = 'bi-file-earmark-play text-secondary';
                                            elseif (in_array($ext, ['zip','rar'])) $icon = 'bi-file-earmark-zip text-muted';
                                        @endphp
                                        <i class="bi {{ $icon }} fs-1 mb-2"></i>
                                        <div class="fw-semibold small text-break mb-2">
                                            @php
                                                $imageExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                                                $ext = strtolower(pathinfo($file->file_name, PATHINFO_EXTENSION));
                                            @endphp
                                            @if(in_array($ext, $imageExts))
                                                <img src="{{ Storage::url($file->file_path) }}" class="card-img-top" style="height:180px; object-fit:cover;">
                                            @else
                                                {{ $file->file_name }}
                                            @endif
                                        </div>
                                        <a href="{{ Storage::url($file->file_path) }}" target="_blank" class="btn btn-sm btn-outline-primary w-100">Xem / Tải xuống</a>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="alert alert-light border text-center mb-0">Chưa có tài liệu đính kèm</div>
                            </div>
                        @endforelse
                    </div>
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