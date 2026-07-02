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

        {{-- Web Table View --}}
        <div class="d-none d-md-block">
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
                                    $detailRoute = route('maintenance-system.show', $item->id);
                                    $currentStatus = $item->status;
                                    $workflow = config('maintenance_system.workflow');
                                    $statusNamesHt = config('sla_status.names_ht_func');
                                    $nextStatuses = $workflow[$currentStatus] ?? [];
                                @endphp
                                <tr data-id="{{ $item->id }}" class="tr-row-link" data-detail-url="{{ $detailRoute }}">
                                    <td class="text-center">
                                        {{ $items->firstItem() + $loop->index }}
                                    </td>
                                    <td style="min-width: 300px;max-width: 350px;">
                                        Mã: {{ $item->issue_code }} <br>
                                        <div class="fw-semibold">
                                            {{ $item->issue_name }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ $item->issue_description }}
                                        </div>
                                    </td>
                                    <td style="min-width: 200px;max-width: 250px;">
                                        {{ $item->branch_name }}
                                        <div class="text-muted small">{{ $item->branch_code }}</div>
                                    </td>
                                    <td style="min-width: 200px;max-width: 250px;">
                                        {{ $item->technician_name }}
                                        <div class="text-muted small">{{ $item->technician_email }}</div>
                                    </td>
                                    <td style="min-width: 100px;max-width: 150px;">
                                        @php
                                            $sla_configs = config('real_time');
                                            $sla_display = '-';
                                            if (!empty($item->standard_completion_time)) {
                                                $configItem = collect($sla_configs)->firstWhere('key', $item->standard_completion_time);
                                                if ($configItem) {
                                                    $sla_display = $configItem['name'];
                                                } else {
                                                    $sla_display = $item->standard_completion_time;
                                                }
                                            }
                                        @endphp
                                        {{ $sla_display }}
                                    </td>
                                    <td style="min-width: 300px;max-width: 350px;">
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
                                                @hasanyrole('technician_system|admin')
                                                @can('change-system-status')
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

        {{-- Mobile Card View --}}
        <div class="d-block d-md-none">
            <div class="row g-3">
                @forelse($items as $item)
                @php
                    $slaStatusBadge = data_get($StatusBadgeList, $item->status, 'badge badge-default');
                    $slaStatusName = data_get($statuses, $item->status, $item->status);
                    $detailRoute = route('maintenance-system.show', $item->id);
                    $currentStatus = $item->status;
                    $workflow = config('maintenance_system.workflow');
                    $statusNamesHt = config('sla_status.names_ht_func');
                    $nextStatuses = $workflow[$currentStatus] ?? [];
                    $sla_configs = config('real_time');
                    $sla_display = '-';
                    if (!empty($item->standard_completion_time)) {
                        $configItem = collect($sla_configs)->firstWhere('key', $item->standard_completion_time);
                        if ($configItem) {
                            $sla_display = $configItem['name'];
                        } else {
                            $sla_display = $item->standard_completion_time;
                        }
                    }
                @endphp
                <div class="col-12">
                    <div class="card mobile-row-link" style="cursor:pointer" data-url="{{ $detailRoute }}">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="status_badge {{ $slaStatusBadge }}">
                                    {{ $slaStatusName }}
                                </span>
                                <span class="badge {{ $item->is_confirmed ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $item->is_confirmed ? 'Xác nhận nghiệm thu' : 'Chưa xác nhận nghiệm thu' }}
                                </span>
                            </div>
                            <h6 class="mt-3 mb-1 text-primary fw-bold">
                                {{ $item->issue_name }}
                            </h6>
                            <div class="mb-1 small">
                                <span class="fw-semibold">Mã:</span> {{ $item->issue_code }}<br>
                                <span class="fw-semibold text-muted">{{ $item->issue_description }}</span>
                            </div>
                            <div class="mb-1 small">
                                <span class="fw-semibold">Chi nhánh:</span> {{ $item->branch_name }} ({{ $item->branch_code }})<br>
                                <span class="fw-semibold">KTV:</span> {{ $item->technician_name }} ({{ $item->technician_email }})
                            </div>
                            <div class="mb-1 small">
                                <span class="fw-semibold">SLA:</span> {{ $sla_display }}
                            </div>
                            <div class="mb-1 small">
                                <span class="fw-semibold">Ngày Yêu Cầu:</span> {{ $item->request_date ? \Carbon\Carbon::parse($item->request_date)->format('d/m/Y H:i:s') : '' }}
                            </div>
                            <div class="mb-1 small">
                                <span class="fw-semibold">Yêu cầu hoàn thành:</span> {{ $item->standard_completion_time }}
                            </div>
                            @if($item->actual_completion_date)
                                <div class="mb-1 small">
                                    <span class="fw-semibold">Ngày hoàn thành:</span> {{ \Carbon\Carbon::parse($item->actual_completion_date)->format('d/m/Y H:i:s') }}
                                </div>
                            @endif
                            @if($item->actual_duration)
                                <div class="mb-1 small">
                                    <span class="fw-semibold">Thời gian thực tế:</span> {{ ($item->actual_duration) }}
                                </div>
                            @endif
                            <!-- <div class="mb-1 small">
                                <span class="fw-semibold">Tạo:</span> {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i:s') }}
                            </div>
                            <div class="mb-2 small">
                                <span class="fw-semibold">Cập nhật:</span> {{ \Carbon\Carbon::parse($item->updated_at)->format('d/m/Y H:i:s') }}
                            </div> -->
                            <div class="dropdown mt-2">
                                <button class="btn btn-primary btn-sm dropdown-toggle action-btn w-100" type="button"
                                    data-bs-toggle="dropdown">Thao tác</button>
                                <ul class="dropdown-menu w-100">
                                    @hasanyrole('technician_system|admin')
                                    @can('change-system-status')
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
                        </div>
                    </div>
                </div>
                @empty
                <div class="col-12">
                    <div class="card text-center py-4 text-muted">
                        Chưa có dữ liệu.
                    </div>
                </div>
                @endforelse
            </div>
            <div class="card-footer bg-white">
                {{ $items->links() }}
            </div>
        </div>
    </div>

    {{-- Modal --}}
    @include('system.modals.modal')
    @include('system.modals.acceptance')
    @include('system.modals.change-status-admin')
    <style>
        .tr-row-link { cursor: pointer; }
        .mobile-row-link { cursor: pointer; }
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
                if(e.target.closest('a,button,.dropdown,.dropdown-menu')) return;
                window.location.href = this.dataset.url;
            });
        });
    });
    </script>
    <script>
        /**
         * Hiển thị modal bảo trì hệ thống (pattern follow permissions).
         * @param {'create'|'edit'|'detail'|'status'} action
         * @param {number|null} id
         * @param {string|null} status
         */
        function openMaintenanceModal(action, id = null, status = null) {
            if (typeof window.openMaintenanceModalBase === "function") {
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

                        if (action === 'status') {
                            setTimeout(function () {
                                var form = document.querySelector('#maintenanceModalContent form');
                                if (form) {
                                    form.addEventListener('submit', function (e) {
                                        e.preventDefault();

                                        var formData = new FormData(form);
                                        var actionUrl = form.getAttribute('action');

                                        var errorBox = document.getElementById('system-form-errors');
                                        if (errorBox) {
                                            errorBox.classList.add('d-none');
                                            errorBox.innerHTML = '';
                                        }

                                        fetch(actionUrl, {
                                            method: 'POST',
                                            headers: {
                                                'X-Requested-With': 'XMLHttpRequest',
                                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content
                                            },
                                            body: formData
                                        })
                                            .then(async res => {
                                                if (res.ok) {
                                                    location.reload();
                                                } else if (res.status === 422) {
                                                    const json = await res.json();
                                                    if (errorBox) {
                                                        errorBox.classList.remove('d-none');
                                                        errorBox.innerHTML = Object.values(json.errors).map(x => x.join(', ')).join('<br>');
                                                    }
                                                } else if (res.status === 403) {
                                                    if (errorBox) {
                                                        errorBox.classList.remove('d-none');
                                                        errorBox.innerHTML = 'Bạn không có quyền thực hiện!';
                                                    }
                                                } else {
                                                    if (errorBox) {
                                                        errorBox.classList.remove('d-none');
                                                        errorBox.innerHTML = 'Lỗi không xác định!';
                                                    }
                                                }
                                            })
                                            .catch(function () {
                                                if (errorBox) {
                                                    errorBox.classList.remove('d-none');
                                                    errorBox.innerHTML = 'Không thể gửi yêu cầu. Vui lòng thử lại!';
                                                }
                                            });
                                    });
                                }
                            }, 20);
                        }

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

            syncScrollWidth();
            window.addEventListener('resize', syncScrollWidth);

            if (window.ResizeObserver) {
                const observer = new ResizeObserver(syncScrollWidth);
                observer.observe(table);
            }

            topScroll.addEventListener('scroll', function () {
                if (tableResponsive.scrollLeft !== topScroll.scrollLeft) {
                    tableResponsive.scrollLeft = topScroll.scrollLeft;
                }
            });

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