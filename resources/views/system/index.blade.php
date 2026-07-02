@php
    $title = 'Danh sách bảo trì hạ tầng';
    $statuses = config('sla_status.names_ht');
    $statusOptions = config('sla_status.code_ht');
    $fromDate = request('from_date', now()->startOfMonth()->format('Y-m-d'));
    $toDate = request('to_date', now()->endOfMonth()->format('Y-m-d'));
    $acceptanceList = collect(config('acceptance'))->pluck('name', 'key');
    $StatusBadgeList = config('sla_status.badge');
    $slaStatusCode = config('sla_status.code_ht');
@endphp

<x-app-layout :title="$title">
    <x-slot name="header">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h3 class="mb-0 fs-5 fs-md-3">{{ $title }}</h3>
            <div class="d-flex flex-column flex-md-row gap-2">
                @can('create data')
                    <button class="btn btn-outline-primary flex-fill mt-2 mt-md-0" onclick="openMaintenanceModal('create')"
                        type="button">
                        <i class="bi bi-plus-circle"></i> Thêm mới
                    </button>
                @endcan
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body search-card">
                <form method="GET" action="{{ route('maintenance-system.index') }}">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label for="keyword" class="form-label mb-1">Từ khoá</label>
                            <input type="text" name="keyword" id="system-tech-search" class="form-control"
                                value="{{ request('keyword') }}" placeholder="Mã lỗi / Sự cố / Chi nhánh / KTV">
                        </div>
                        <div class="col-md-2">
                            <label for="from_date" class="form-label mb-1">Ngày yêu cầu (Từ)</label>
                            <input type="date" name="from_date" id="from_date" class="form-control"
                                value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-2">
                            <label for="to_date" class="form-label mb-1">Ngày yêu cầu (Đến)</label>
                            <input type="date" name="to_date" id="to_date" class="form-control" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label mb-1">Trạng thái</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">-- Trạng thái --</option>
                                @foreach($statusOptions as $key => $value)
                                    <option value="{{ $key }}" @selected(request('status') == $key)>
                                        {{ $statuses[$key] ?? $key }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 d-flex gap-2 pt-md-3">
                            <button type="submit" class="btn btn-primary flex-fill mt-2 mt-md-0">Tìm kiếm</button>
                            <a href="{{ route('maintenance-system.index') }}"
                                class="btn btn-outline-secondary flex-fill mt-2 mt-md-0">Bỏ lọc</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="card-body p-0">
        @if(session('success'))
            <div class="alert alert-success mb-0 p-3 border-0 rounded-0">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger mb-0 p-3 border-0 rounded-0">
                <ul class="mb-0 ps-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <div class="card">
            <div class="table-scroll-top-system">
                <div></div>
            </div>
            <div class="table-responsive">
                <table id="tblSystem" class="table table-bordered table-striped">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center" style="width: 48px;">STT</th>
                            <th scope="col">Sự cố</th>
                            <th scope="col">Chi nhánh</th>
                            <th scope="col">KTV</th>
                            <th scope="col">SLA</th>
                            <th scope="col">Thời gian</th>
                            <th scope="col" class="text-center">Trạng thái</th>
                            <th scope="col" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            @php
                                $slaStatusBadge = data_get($StatusBadgeList, $item->status, 'badge badge-default');
                                $slaStatusName = data_get($statuses, $item->status, $item->status);
                            @endphp
                            <tr>
                                <td class="text-center">
                                    {{ $items->firstItem() + $loop->index }}
                                </td>
                                <td style=" min-width: 300px;max-width: 350px;">
                                    Mã: {{ $item->issue_code }} <br>
                                    <div class="fw-semibold">
                                        {{ $item->issue_name }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ $item->issue_description }}
                                    </div>
                                </td>
                                <td style=" min-width: 200px;max-width: 250px;">
                                    {{ $item->branch_name }}
                                    <div class="text-muted small">{{ $item->branch_code }}</div>
                                </td>
                                <td style=" min-width: 200px;max-width: 250px;">
                                    {{ $item->technician_name }}
                                    <div class="text-muted small">{{ $item->technician_email }}</div>
                                </td>
                                <td style=" min-width: 100px;max-width: 150px;">
                                    @php
                                        $sla_configs = config('real_time');
                                        $sla_display = '-';
                                        if (!empty($item->standard_completion_time)) {
                                            $configItem = collect($sla_configs)->firstWhere('key', $item->standard_completion_time);
                                            if ($configItem) {
                                                $sla_display = $configItem['name'];
                                            } else {
                                                $sla_display = str_replace('_', ' ', $item->standard_completion_time);
                                            }
                                        }
                                    @endphp
                                    {{ $sla_display }}
                                </td>
                                <td style=" min-width: 300px;max-width: 350px;">
                                    Ngày yêu cầu:
                                    {{ $item->request_date ? \Carbon\Carbon::parse($item->request_date)->format('d/m/Y H:i:s') : '' }}<br>
                                    Yêu cầu hoàn thành: {{ $item->standard_completion_time }}
                                    @if($item->actual_completion_date)
                                        <br>Ngày hoàn thành:
                                        {{ \Carbon\Carbon::parse($item->actual_completion_date)->format('d/m/Y H:i:s') }}
                                    @endif
                                    @if($item->actual_duration)
                                        <br>Thời gian thực tế: {{ ($item->actual_duration) }}
                                    @endif
                                    <hr>
                                    Tạo: {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i:s') }}<br>
                                    Cập nhật: {{ \Carbon\Carbon::parse($item->updated_at)->format('d/m/Y H:i:s') }}
                                </td>
                                <td class="text-center">
                                    <span class="status_badge {{ $slaStatusBadge }}">
                                        {{ $slaStatusName }}
                                    </span><br>
                                    <span class="badge {{ $item->is_confirmed ? 'bg-success' : 'bg-secondary' }}">
                                        {{ $item->is_confirmed ? 'Xác nhận nghiệm thu' : 'Chưa xác nhận nghiệm thu' }}
                                    </span>
                                </td>
                                <td class="action-column action-cell">
                                    <div class="dropdown">
                                        <button class="btn btn-primary dropdown-toggle action-btn" type="button"
                                            data-bs-toggle="dropdown">Thao tác</button>
                                        <ul class="dropdown-menu">
                                            <li>
                                                <a href="javascript:void(0)"
                                                    onclick="openMaintenanceModal('detail',{{ $item->id }})"
                                                    class="dropdown-item text-success">
                                                    <i class="bi bi-eye"></i> Chi tiết
                                                </a>
                                            </li>
                                            @hasanyrole('technician_system|admin')
                                            @can('change-system-status')
                                                @php
                                                    // Lấy workflow từ config
                                                    $workflow = config('maintenance_system.workflow');
                                                    $statusNamesHt = config('sla_status.names_ht_func');
                                                    $currentStatus = $item->status;
                                                    $nextStatuses = $workflow[$currentStatus] ?? [];
                                                @endphp

                                                @if(!in_array($currentStatus, ['COMPLETED', 'LATED']))
                                                    @foreach ($nextStatuses as $nextStatus)
                                                        @if(
                                                                ($currentStatus === 'CONFIRMED' && auth()->user()->hasRole('admin')) ||
                                                                ($currentStatus !== 'CONFIRMED')
                                                            )
                                                            <li>
                                                                <a href="javascript:void(0)"
                                                                    onclick="openMaintenanceModal('status',{{ $item->id }},'{{ $nextStatus }}')"
                                                                    class="dropdown-item">
                                                                    {{ $statusNamesHt[$nextStatus] ?? str_replace('_', ' ', $nextStatus) }}
                                                                </a>
                                                            </li>
                                                        @endif
                                                    @endforeach
                                                @endif

                                            @endcan
                                            @endhasanyrole

                                            @role('admin')
                                            <li>
                                                <a href="javascript:void(0)"
                                                    class="dropdown-item admin-change-status-system-btn"
                                                    data-bs-toggle="modal" data-bs-target="#changeStatusSystemModal"
                                                    data-id="{{ $item->id }}" data-current-status="{{ $item->status }}">
                                                    Đổi trạng thái
                                                </a>
                                            </li>
                                            @if($item->status == 'NEW')
                                                <li>
                                                    <a href="javascript:void(0)"
                                                        onclick="openMaintenanceModal('edit',{{ $item->id }})"
                                                        class="dropdown-item text-primary">
                                                        <i class="bi bi-pencil"></i> Sửa
                                                    </a>
                                                </li>
                                            @endif
                                            @if (in_array($currentStatus, ['COMPLETED', 'LATED']) && !$item->is_confirmed)
                                                <li>
                                                    <a href="javascript:void(0)" class="dropdown-item acceptance-system-btn"
                                                        data-bs-toggle="modal" data-bs-target="#acceptanceSystemModal"
                                                        data-id="{{ $item->id }}">
                                                        Nghiệm thu
                                                    </a>
                                                </li>
                                            @endif
                                            <li>
                                                <form action="{{ route('maintenance-system.destroy', $item->id) }}"
                                                    method="POST" onsubmit="return confirm('Xóa bản ghi này?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="dropdown-item text-danger">
                                                        <i class="bi bi-trash"></i> Xóa
                                                    </button>
                                                </form>
                                            </li>
                                            @endrole
                                        </ul>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    Chưa có dữ liệu.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">
            {{ $items->links() }}
        </div>
    </div>

    {{-- Modal --}}
    <div id="maintenanceModal" class="modal fade" tabindex="-1" aria-labelledby="maintenanceModalTitle"
        aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 id="maintenanceModalTitle" class="modal-title"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div id="system-form-errors" class="alert alert-danger d-none"></div>
                <div id="maintenanceModalContent" class="modal-body"></div>
            </div>
        </div>
    </div>
    @include('system.modals.acceptance')
    @include('system.modals.change-status-admin')

    <script>
        /**
         * Hiển thị modal bảo trì hệ thống (pattern follow permissions).
         * @param {'create'|'edit'|'detail'|'status'} action
         * @param {number|null} id
         * @param {string|null} status
         */
        function openMaintenanceModal(action, id = null, status = null) {
            if (typeof window.openMaintenanceModalBase === "function") {
                // Nếu JS gốc đã khai báo, gọi hàm chuẩn dùng chung ở maintenance.js
                window.openMaintenanceModalBase({
                    urlCreate: "{{ route('maintenance-system.create') }}",
                    urlEdit: id ? `/maintenance-system/${id}/edit` : null,
                    urlDetail: id ? `/maintenance-system/${id}` : null,
                    urlChangeStatus: (id && status) ? `/maintenance-system/${id}/change-status/${status}` : null,
                    action, id, status,
                    modalId: 'maintenanceModal',
                    modalTitleId: 'maintenanceModalTitle',
                    modalContentId: 'maintenanceModalContent'
                });
            } else {
                // Tạm fallback: logic tự động fetch và hiển thị modal trong trường hợp JS base chưa được include
                let url = '';
                let title = '';

                if (action === 'create') {
                    url = "{{ route('maintenance-system.create') }}";
                    title = 'Tạo yêu cầu';
                } else if (action === 'edit') {
                    url = id ? `/maintenance-system/${id}/edit` : '';
                    title = 'Cập nhật';
                } else if (action === 'detail') {
                    url = id ? `/maintenance-system/${id}` : '';
                    title = 'Chi tiết';
                } else if (action === 'status') {
                    url = (id && status) ? `/maintenance-system/${id}/change-status/${status}` : '';
                    title = 'Đổi trạng thái';
                }

                if (!url) return;

                fetch(url)
                    .then(res => res.text())
                    .then(html => {
                        document.getElementById('maintenanceModalTitle').innerText = title;
                        document.getElementById('maintenanceModalContent').innerHTML = html;
                        bootstrap.Modal
                            .getOrCreateInstance(document.getElementById('maintenanceModal'))
                            .show();
                    });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {

            const topScroll = document.querySelector('.table-scroll-top-system');
            const topScrollInner = topScroll ? topScroll.querySelector('div') : null;
            const tableResponsive = document.querySelector('.table-responsive');
            const table = document.querySelector('#tblSystem');

            if (!topScroll || !topScrollInner || !tableResponsive || !table) {
                return;
            }

            function syncScrollWidth() {
                topScrollInner.style.width = table.scrollWidth + 'px';
            }

            // Đồng bộ chiều rộng khi load
            syncScrollWidth();

            // Đồng bộ khi resize
            window.addEventListener('resize', syncScrollWidth);

            // Đồng bộ khi bảng thay đổi kích thước (nếu trình duyệt hỗ trợ)
            if (window.ResizeObserver) {
                const observer = new ResizeObserver(syncScrollWidth);
                observer.observe(table);
            }

            // Scroll trên -> dưới
            topScroll.addEventListener('scroll', function () {
                if (tableResponsive.scrollLeft !== topScroll.scrollLeft) {
                    tableResponsive.scrollLeft = topScroll.scrollLeft;
                }
            });

            // Scroll dưới -> trên
            tableResponsive.addEventListener('scroll', function () {
                if (topScroll.scrollLeft !== tableResponsive.scrollLeft) {
                    topScroll.scrollLeft = tableResponsive.scrollLeft;
                }
            });

        });

    </script>
    <style>
        .table-scroll-top-system {
            overflow-x: auto;
            overflow-y: hidden;
            height: 16px;
            margin-bottom: 5px;
        }

        .table-scroll-top-system div {
            height: 1px;
        }
    </style>

</x-app-layout>