@php
    $config = config();
    $severities = collect($config['severities'] ?? [])->keyBy('key');
    $acceptanceList = collect($config['acceptance'] ?? [])->pluck('name', 'key');
    $realTimeMap = collect($config['real_time'] ?? [])->keyBy('key');
    $sla = $realTimeMap[$maintenanceRequest->standard_completion_time] ?? null;
    $severityName = $severities[$maintenanceRequest->severity]['name'] ?? $maintenanceRequest->severity;
    $slaStatusBadge = data_get($config['sla_status.badge'] ?? [], $maintenanceRequest->sla_status, 'badge badge-default');
    $slaStatusName = data_get($config['sla_status.names'] ?? [], $maintenanceRequest->sla_status, $maintenanceRequest->sla_status);
    $timeName = $maintenanceRequest->standard_completion_time
        ? ($realTimeMap[$maintenanceRequest->standard_completion_time]['name'] ?? $maintenanceRequest->standard_completion_time)
        : '';
@endphp

<ul class="nav nav-tabs mb-2" id="maintenanceTab" role="tablist">
    @foreach([
        ['id' => 'info', 'label' => 'Thông tin chung', 'active' => true],
        ['id' => 'images', 'label' => 'Hình ảnh'],
        ['id' => 'history', 'label' => 'Lịch sử trạng thái'],
        ['id' => 'update', 'label' => 'Lịch sử Cập nhật'],
    ] as $tab)
        <li class="nav-item" role="presentation">
            <button 
                class="nav-link{{ !empty($tab['active']) ? ' active' : '' }}"
                id="{{ $tab['id'] }}-tab"
                data-bs-toggle="tab"
                data-bs-target="#{{ $tab['id'] }}-tab-pane"
                type="button"
                role="tab"
                aria-controls="{{ $tab['id'] }}-tab-pane"
                aria-selected="{{ !empty($tab['active']) ? 'true' : 'false' }}"
            >{{ $tab['label'] }}</button>
        </li>
    @endforeach
</ul>

<div class="tab-content" id="maintenanceTabContent">
    {{-- Thông tin chung --}}
    <div class="tab-pane fade show active" id="info-tab-pane" role="tabpanel" aria-labelledby="info-tab">
        <div class="d-flex flex-wrap gap-2">
            @php
                $cards = [
                    [
                        'title' => 'Sự cố',
                        'rows' => [
                            ['label' => 'ID', 'value' => $maintenanceRequest->id],
                            ['label' => 'Cơ sở', 'value' => $maintenanceRequest->branch_code.' - '.$maintenanceRequest->branch_name],
                            ['label' => 'Tình trạng', 'raw' => "<span class=\"$slaStatusBadge\">$slaStatusName</span>"],
                            ['label' => 'Loại', 'value' => $severityName],
                            ['label' => 'Nghiệm thu', 'raw' => '<span class="badge '.($maintenanceRequest->is_confirmed ? 'bg-success':'bg-secondary').'">'.($maintenanceRequest->is_confirmed ? 'Đã nghiệm thu':'Chưa nghiệm thu').'</span>'],
                            ['label' => 'KQ nghiệm thu', 'value' => $acceptanceList[$maintenanceRequest->acceptance_result] ?? $maintenanceRequest->acceptance_result],
                            ['label' => 'Người xác nhận', 'value' => $maintenanceRequest->acceptance_confirmed_by],
                            ['label' => 'Đơn vị xử lý', 'value' => $maintenanceRequest->outsourced_provider]
                        ]
                    ],
                    [
                        'title' => 'Mô tả',
                        'rows' => array_merge(
                            [['label' => 'Hạng mục', 'value' => $maintenanceRequest->item_category]],
                            collect([
                                ['label' => 'Sự cố', 'field' => 'issue_description'],
                                ['label' => 'Giải pháp', 'field' => 'solution_description'],
                                ['label' => 'Lý do trễ', 'field' => 'delay_reason']
                            ])->map(fn($d) => [
                                'label' => $d['label'],
                                'raw' => nl2br(e($maintenanceRequest->{$d['field']}))
                            ])->toArray()
                        )
                    ],
                    [
                        'title' => 'Thời gian & Tiến trình',
                        'rows' => [
                            ['label' => 'Ngày làm', 'raw' => 
                                '<span class="badge bg-secondary me-1">Hành chính</span>'
                                .($maintenanceRequest->include_saturday ? '<span class="badge bg-primary me-1">T7</span>' : '')
                                .($maintenanceRequest->include_sunday ? '<span class="badge bg-info me-1">CN</span>' : '')
                                .($maintenanceRequest->include_holiday ? '<span class="badge bg-warning me-1">Lễ</span>' : '')
                            ],
                            ['label' => 'Hạn', 'value' => $timeName],
                            ['label' => 'YC', 'value' => $maintenanceRequest->request_date ? \Carbon\Carbon::parse($maintenanceRequest->request_date)->format('d/m/Y H:i') : ''],
                            ...collect([['pending_at', 'Tạm dừng'], ['processing_at', 'Tiếp tục']])->filter(fn($pair)=>$maintenanceRequest->{$pair[0]})->map(fn($pair)=>[
                                'label' => $pair[1],
                                'value' => \Carbon\Carbon::parse($maintenanceRequest->{$pair[0]})->format('d/m/Y H:i')
                            ])->toArray(),
                            ['label' => 'HT', 'value' => $maintenanceRequest->actual_completion_date? \Carbon\Carbon::parse($maintenanceRequest->actual_completion_date)->format('d/m/Y H:i') : ''],
                            ['label' => 'TG thực tế', 'value' => $maintenanceRequest->actual_duration ? format_duration($maintenanceRequest->actual_duration) : ''],
                            ['label' => 'Tạo', 'value' => \Carbon\Carbon::parse($maintenanceRequest->created_at)->format('d/m/Y H:i')],
                            ['label' => 'Cập nhật', 'value' => \Carbon\Carbon::parse($maintenanceRequest->updated_at)->format('d/m/Y H:i')],
                            [
                                'label' => 'Nhắc',
                                'raw' =>
                                    $maintenanceRequest->reminder_count
                                    . ($maintenanceRequest->last_reminded_at
                                        ? '<br><b>Lần cuối:</b> '.\Carbon\Carbon::parse($maintenanceRequest->last_reminded_at)->format('d/m/Y H:i')
                                        : ''
                                    )
                            ]
                        ]
                    ],
                    [
                        'title' => 'Kỹ thuật viên',
                        'rows' => collect([
                            ['label' => 'Tên', 'field' => 'technician_name'],
                            ['label' => 'SĐT', 'field' => 'technician_mobile'],
                            ['label' => 'Email', 'field' => 'technician_email']
                        ])->map(fn($d)=>[
                            'label'=>$d['label'],
                            'value'=>$maintenanceRequest->{$d['field']}
                        ])->toArray()
                    ]
                ];
            @endphp
            @foreach($cards as $card)
                <div class="card flex-fill" style="min-width:240px;max-width:360px">
                    <div class="card-header bg-light fw-semibold py-2 small">{{ $card['title'] }}</div>
                    <div class="card-body pb-1 px-2 small d-flex flex-column gap-1">
                        @foreach($card['rows'] as $row)
                            <div>
                                <b>{{ $row['label'] }}:</b> {!! $row['raw'] ?? e($row['value'] ?? '') !!}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Hình ảnh --}}
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
                                <td>{{ optional($log->created_at)->format('d/m/Y H:i') }}</td>
                                <td>{{ $log->user['name'] ?? '' }}</td>
                                <td>{{ data_get($config['sla_status.names'] ?? [], $log->old_status, $log->old_status ?? '') }}</td>
                                <td>{{ data_get($config['sla_status.names'] ?? [], $log->new_status, $log->new_status ?? '') }}</td>
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

    {{-- Lịch sử cập nhật --}}
    <div class="tab-pane fade" id="update-tab-pane" role="tabpanel" aria-labelledby="update-tab">
        <div class="card border-0">
            <div class="card-header fw-semibold py-2 small">Lịch sử cập nhật</div>
            <div class="card-body p-0">
                <table class="table table-bordered table-hover mb-0 align-middle text-center small">
                    <thead class="table-light">
                        <tr>
                            <th width="200">Thời gian</th>
                            <th width="200">Người cập nhật</th>
                            <th width="200">Nội dung thay đổi</th>
                            <th>Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($maintenanceRequest->updateLogs as $log)
                            <tr>
                                <td>{{ $log->created_at->format('d/m/Y H:i') }}</td>
                                <td>{{ optional($log->user)->name }}</td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary btn-show-update-log"
                                        data-bs-toggle="modal"
                                        data-bs-target="#updateLogDetailModal"
                                        data-time="{{ $log->created_at->format('d/m/Y H:i') }}"
                                        data-user="{{ optional($log->user)->name }}"
                                        data-note="{{ $log->note }}"
                                        data-details='@json($log->details)'>
                                        <i class="fas fa-eye"></i>
                                        Xem chi tiết
                                        <span class="badge bg-primary">{{ $log->details->count() }}</span>
                                    </button>
                                </td>
                                <td>{{ $log->note }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Chưa có lịch sử cập nhật</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateLogDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết cập nhật</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="220">Trường</th>
                            <th>Giá trị cũ</th>
                            <th>Giá trị mới</th>
                        </tr>
                    </thead>
                    <tbody id="updateLogDetailBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
.image-card { transition: all .2s ease; }
.image-card:hover { transform: translateY(-3px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15); }
.delete-image { width: 34px; height: 34px; padding: 0; opacity: .85; }
.delete-image:hover { opacity: 1; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    $(document).on('click', '.btn-show-update-log', function () {
        const btn = $(this);
        let details = btn.data('details');
        if (typeof details === 'string') details = JSON.parse(details);
        let html = details.length
            ? details.map(item => `<tr><td>${item.field_name}</td><td>${item.old_value ?? ''}</td><td>${item.new_value ?? ''}</td></tr>`).join('')
            : '<tr><td colspan="3" class="text-center text-muted">Không có dữ liệu</td></tr>';
        $('#updateLogDetailBody').html(html);
    });
});
</script>