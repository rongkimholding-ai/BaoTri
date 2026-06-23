@php
    $severities = collect(config('severities'))->keyBy('key');
    $acceptanceList = collect(config('acceptance'))->pluck('name', 'key');
    $realTimeList = config('real_time');
    $realTimeMap = collect($realTimeList)->keyBy('key');
    $sla = $realTimeMap[$maintenanceRequest->standard_completion_time] ?? null;

    $severityName = $severities[$maintenanceRequest->severity]['name'] ?? $maintenanceRequest->severity;
    $slaStatusBadge = data_get(config('sla_status.badge'), $maintenanceRequest->sla_status, 'badge badge-default');
    $slaStatusName = data_get(config('sla_status.names'), $maintenanceRequest->sla_status, $maintenanceRequest->sla_status);
    $timeName = $maintenanceRequest->standard_completion_time
        ? ($realTimeMap[$maintenanceRequest->standard_completion_time]['name'] ?? $maintenanceRequest->standard_completion_time)
        : '';
@endphp

<ul class="nav nav-tabs mb-2" id="maintenanceTab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info-tab-pane" type="button"
            role="tab" aria-controls="info-tab-pane" aria-selected="true">
            Thông tin chung
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="images-tab" data-bs-toggle="tab" data-bs-target="#images-tab-pane" type="button"
            role="tab" aria-controls="images-tab-pane" aria-selected="false">
            Hình ảnh
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="history-tab" data-bs-toggle="tab" data-bs-target="#history-tab-pane" type="button"
            role="tab" aria-controls="history-tab-pane" aria-selected="false">
            Lịch sử
        </button>
    </li>
</ul>

<div class="tab-content" id="maintenanceTabContent">
    <!-- Thông tin chung -->
    <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" aria-labelledby="info-tab">
        <div class="d-flex flex-wrap gap-2">
            <div class="card flex-fill" style="min-width:240px;max-width:360px">
                <div class="card-header bg-light fw-semibold py-2 small">Sự cố</div>
                <div class="card-body pb-1 px-2 small d-flex flex-column gap-1">
                    <div><span class="fw-bold">ID: </span>{{ $maintenanceRequest->id }}</div>
                    <div><span class="fw-bold">Cơ sở: </span>{{ $maintenanceRequest->branch_code }} - {{ $maintenanceRequest->branch_name }}</div>
                    <div><span class="fw-bold">Tình trạng: </span><span class="{{ $slaStatusBadge }}">{{ $slaStatusName }}</span></div>
                    <div><span class="fw-bold">Loại: </span>{{ $severityName }}</div>
                    <div><span class="fw-bold">Nghiệm thu: </span>
                        <span class="badge {{ $maintenanceRequest->is_confirmed ? 'bg-success' : 'bg-secondary' }}">
                            {{ $maintenanceRequest->is_confirmed ? 'Đã nghiệm thu' : 'Chưa nghiệm thu' }}
                        </span>
                    </div>
                    <div><span class="fw-bold">KQ nghiệm thu: </span>
                        {{ $acceptanceList[$maintenanceRequest->acceptance_result] ?? $maintenanceRequest->acceptance_result }}
                    </div>
                    <div><span class="fw-bold">Người xác nhận: </span>{{ $maintenanceRequest->acceptance_confirmed_by }}</div>
                    <div><span class="fw-bold">Đơn vị xử lý: </span>{{ $maintenanceRequest->outsourced_provider }}</div>
                </div>
            </div>
            <div class="card flex-fill" style="min-width:240px;max-width:360px">
                <div class="card-header bg-light fw-semibold py-2 small">Mô tả</div>
                <div class="card-body pb-1 px-2 small d-flex flex-column gap-1">
                    <div><span class="fw-bold">Hạng mục: </span>{{ $maintenanceRequest->item_category }}</div>
                    <div><span class="fw-bold">Sự cố: </span>{!! nl2br(e($maintenanceRequest->issue_description)) !!}</div>
                    <div><span class="fw-bold">Giải pháp: </span>{!! nl2br(e($maintenanceRequest->solution_description)) !!}</div>
                    <div><span class="fw-bold">Lý do trễ: </span>{!! nl2br(e($maintenanceRequest->delay_reason)) !!}</div>
                </div>
            </div>
            <div class="card flex-fill" style="min-width:240px;max-width:360px">
                <div class="card-header bg-light fw-semibold py-2 small">Thời gian & Tiến trình</div>
                <div class="card-body pb-1 px-2 small d-flex flex-column gap-1">
                    <div><span class="fw-bold">Ngày làm: </span>
                        <span class="badge bg-secondary me-1">Hành chính</span>
                        {!! 
                            ($maintenanceRequest->include_saturday ? '<span class="badge bg-primary me-1">T7</span>' : '') .
                            ($maintenanceRequest->include_sunday ? '<span class="badge bg-info me-1">CN</span>' : '') .
                            ($maintenanceRequest->include_holiday ? '<span class="badge bg-warning me-1">Lễ</span>' : '')
                        !!}
                    </div>
                    <div><span class="fw-bold">Hạn: </span>{{ $timeName }}</div>
                    <div><span class="fw-bold">YC: </span>{{ $maintenanceRequest->request_date ? \Carbon\Carbon::parse($maintenanceRequest->request_date)->format('d/m/Y H:i') : '' }}</div>
                    {!! $maintenanceRequest->pending_at ? '<div><span class="fw-bold">Tạm dừng: </span>'.\Carbon\Carbon::parse($maintenanceRequest->pending_at)->format('d/m/Y H:i').'</div>' : '' !!}
                    {!! $maintenanceRequest->processing_at ? '<div><span class="fw-bold">Tiếp tục: </span>'.\Carbon\Carbon::parse($maintenanceRequest->processing_at)->format('d/m/Y H:i').'</div>' : '' !!}
                    <div><span class="fw-bold">HT: </span>{{ $maintenanceRequest->actual_completion_date ? \Carbon\Carbon::parse($maintenanceRequest->actual_completion_date)->format('d/m/Y H:i') : '' }}</div>
                    <div><span class="fw-bold">TG thực tế: </span>{{ $maintenanceRequest->actual_duration ? format_duration($maintenanceRequest->actual_duration) : '' }}</div>
                    <div><span class="fw-bold">Tạo: </span>{{ \Carbon\Carbon::parse($maintenanceRequest->created_at)->format('d/m/Y H:i') }}</div>
                    <div><span class="fw-bold">Cập nhật: </span>{{ \Carbon\Carbon::parse($maintenanceRequest->updated_at)->format('d/m/Y H:i') }}</div>
                    <div>
                        <span class="fw-bold">Nhắc: </span>{{ $maintenanceRequest->reminder_count }}
                        @if($maintenanceRequest->last_reminded_at)
                            <br>
                            <span class="fw-bold">Lần cuối: </span>{{ \Carbon\Carbon::parse($maintenanceRequest->last_reminded_at)->format('d/m/Y H:i') }}
                        @endif
                    </div>
                </div>
            </div>
            <div class="card flex-fill" style="min-width:240px;max-width:360px">
                <div class="card-header bg-light fw-semibold py-2 small">Kỹ thuật viên</div>
                <div class="card-body pb-1 px-2 small d-flex flex-column gap-1">
                    <div><span class="fw-bold">Tên: </span>{{ $maintenanceRequest->technician_name }}</div>
                    <div><span class="fw-bold">SĐT: </span>{{ $maintenanceRequest->technician_mobile }}</div>
                    <div><span class="fw-bold">Email: </span>{{ $maintenanceRequest->technician_email }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hình ảnh -->
    <div class="tab-pane fade" id="images-tab-pane" role="tabpanel" aria-labelledby="images-tab">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold py-2">
                Hình ảnh ({{ $maintenanceRequest->images->count() }})
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse($maintenanceRequest->images as $image)
                        <div class="col-6 col-md-4 col-lg-3 img-item">
                            <div class="card h-100 border shadow-sm image-card">
                                <div class="position-relative">
                                    <a href="{{ Storage::url($image->path) }}" target="_blank">
                                        <img
                                            src="{{ Storage::url($image->path) }}"
                                            class="card-img-top"
                                            style="height:180px; object-fit:cover;">
                                    </a>

                                    @role('admin')
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 m-2 delete-image"
                                            data-id="{{ $image->id }}"
                                            title="Xóa ảnh"
                                        >
                                            X
                                        </button>
                                    @endrole
                                </div>

                                <div class="card-footer bg-white py-2 text-center">
                                    <a
                                        href="{{ Storage::url($image->path) }}"
                                        target="_blank"
                                        class="btn btn-sm btn-outline-primary w-100">
                                        Xem ảnh
                                    </a>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="alert alert-light border text-center mb-0">
                                Chưa có hình ảnh đính kèm
                            </div>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Lịch sử -->
    <div class="tab-pane fade" id="history-tab-pane" role="tabpanel" aria-labelledby="history-tab">
        <div class="card border-0">
            <div class="card-header fw-semibold py-2 small">Lịch sử</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0 align-middle text-center small">
                    <thead class="table-light">
                        <tr>
                            <th>Thời gian</th>
                            <th>Người thực hiện</th>
                            <th>Từ</th>
                            <th>Sang</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maintenanceRequest->logs as $log)
                            <tr>
                                <td>
                                    {{ $log->created_at instanceof \Carbon\Carbon
                                        ? $log->created_at->format('d/m/Y H:i')
                                        : \Carbon\Carbon::parse($log->created_at)->format('d/m/Y H:i') 
                                    }}
                                </td>
                                <td>{{ $log->user['name'] }}</td>
                                <td>{{ data_get(config('sla_status.names'), $log->old_status, $log->old_status ?? '') }}</td>
                                <td>{{ data_get(config('sla_status.names'), $log->new_status, $log->new_status ?? '') }}</td>
                                <td>{{ $log->note ?? '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Chưa có lịch sử</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
{{-- Ensure Bootstrap JS is loaded for tab functionality --}}
<style>
.image-card {
    transition: all .2s ease;
}

.image-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15);
}

.delete-image {
    width: 34px;
    height: 34px;
    padding: 0;
    opacity: .85;
}

.delete-image:hover {
    opacity: 1;
}
</style>