@php
    $title = 'Danh sách bảo trì hạ tầng';
    $statuses = config('sla_status.names_ht');
    $statusOptions = config('sla_status.code_ht');
    $fromDate = request('from_date', now()->startOfMonth()->format('Y-m-d'));
    $toDate = request('to_date', now()->endOfMonth()->format('Y-m-d'));
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
                            <input type="text" name="keyword" id="keyword" class="form-control"
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

    <div class="card">
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

            <div class="table-responsive">
                <table class="table align-middle table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="text-center" style="width: 48px;">STT</th>
                            <th scope="col">Mã lỗi</th>
                            <th scope="col">Sự cố</th>
                            <th scope="col">Chi nhánh</th>
                            <th scope="col">KTV</th>
                            <th scope="col">Ngày yêu cầu</th>
                            <th scope="col">SLA</th>
                            <th scope="col">Trạng thái</th>
                            <th scope="col">Hoàn thành</th>
                            <th scope="col" class="text-center">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $item)
                            <tr>
                                <td class="text-center">
                                    {{ $items->firstItem() + $loop->index }}
                                </td>
                                <td>
                                    {{ $item->issue_code }}
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $item->issue_name }}
                                    </div>
                                    <div class="text-muted small">
                                        {{ Str::limit($item->issue_description, 60) }}
                                    </div>
                                </td>
                                <td>
                                    {{ $item->branch_name }}
                                    <div class="text-muted small">{{ $item->branch_code }}</div>
                                </td>
                                <td>
                                    {{ $item->technician_name }}
                                    <div class="text-muted small">{{ $item->technician_email }}</div>
                                </td>
                                <td>
                                    {{ $item->request_at?->format('d/m/Y H:i') }}
                                </td>
                                <td>
                                    {{ str_replace('_', ' ', $item->completion_time_code) }}
                                </td>
                                <td>
                                    <span class="badge bg-primary">
                                        {{ $statuses[$item->status] ?? $item->status }}

                                    </span>
                                </td>
                                <td>
                                    {{ optional($item->completed_at)->format('d/m/Y H:i') ?? '-' }}
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
                                            @if($item->status == 'NEW')
                                            <li>
                                                <a href="javascript:void(0)"
                                                    onclick="openMaintenanceModal('edit',{{ $item->id }})"
                                                    class="dropdown-item text-primary">
                                                    <i class="bi bi-pencil"></i> Sửa
                                                </a>
                                            </li>
                                            @endif
                                            <!-- <li>
                                                <a href="javascript:void(0)" class="dropdown-item text-warning"
                                                    onclick="openMaintenanceModal('status',{{ $item->id }})">
                                                    <i class="bi bi-arrow-repeat"></i> Trạng thái
                                                </a>
                                            </li> -->
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
                                                    <li>
                                                        <a href="javascript:void(0)"
                                                            onclick="openMaintenanceModal('status',{{ $item->id }},'{{ $nextStatus }}')"
                                                            class="dropdown-item">
                                                            {{ $statusNamesHt[$nextStatus] ?? str_replace('_', ' ', $nextStatus) }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            @endif
                                            @endcan
                                            @endhasanyrole

                                            @role('admin')
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
                <div id="maintenanceModalContent" class="modal-body"></div>
            </div>
        </div>
    </div>

    <script>
        /**
         * Hiển thị modal bảo trì hệ thống (pattern follow permissions).
         * @param {'create'|'edit'|'detail'} action
         * @param {number|null} id
         */
        function openMaintenanceModal(action, id = null, status = null) {

            let url = '';
            let title = '';

            if (action === 'create') {

                url = "{{ route('maintenance-system.create') }}";
                title = 'Tạo yêu cầu';

            } else if (action === 'edit') {

                url = `/maintenance-system/${id}/edit`;
                title = 'Cập nhật';

            } else if (action === 'detail') {

                url = `/maintenance-system/${id}`;
                title = 'Chi tiết';

            } else if (action === 'status') {

                url = `/maintenance-system/${id}/change-status/${status}`;
                title = 'Đổi trạng thái';

            }

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

        // Đóng modal khi bấm ra ngoài (backdrop)
        document.getElementById('maintenanceModal').addEventListener('click', function (e) {
            if (e.target === this) {
                let modal = bootstrap.Modal.getOrCreateInstance(this);
                modal.hide();
            }
        });

        // Đóng modal với ESC
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                let modalEl = document.getElementById('maintenanceModal');
                let modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.hide();
            }
        });

        // Autofocus input khi modal mở
        const modalContent = document.getElementById('maintenanceModalContent');
        const observer = new MutationObserver(() => {
            let input = modalContent.querySelector('input[autofocus]');
            if (input) input.focus();
        });
        observer.observe(modalContent, { childList: true, subtree: true });
    </script>

</x-app-layout>