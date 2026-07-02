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
                        
                        @php
                            $filters = [
                                [
                                    'label' => 'Ngày yêu cầu (Từ)',
                                    'type' => 'date',
                                    'name' => 'from_date',
                                    'id' => 'fromDate',
                                    'value' => $fromDate,
                                ],
                                [
                                    'label' => 'Ngày yêu cầu (Đến)',
                                    'type' => 'date',
                                    'name' => 'to_date',
                                    'id' => 'toDate',
                                    'value' => $toDate,
                                ],
                                [
                                    'label' => 'Ngày hoàn thành (Từ)',
                                    'type' => 'date',
                                    'name' => 'from_date_completed',
                                    'id' => 'fromDateCompleted',
                                    'value' => request('from_date_completed'),
                                ],
                                [
                                    'label' => 'Ngày hoàn thành (Đến)',
                                    'type' => 'date',
                                    'name' => 'to_date_completed',
                                    'id' => 'toDateCompleted',
                                    'value' => request('to_date_completed'),
                                ],
                            ];
                        @endphp
                        @foreach ($filters as $filter)
                            <div class="col-md-3">
                                <label for="{{ $filter['id'] }}" class="form-label mb-1">{{ $filter['label'] }}</label>
                                <input type="{{ $filter['type'] }}" id="{{ $filter['id'] }}" name="{{ $filter['name'] }}" class="form-control"
                                    value="{{ $filter['value'] }}">
                            </div>
                        @endforeach
                        <div class="col-md-3">
                            <label for="keyword" class="form-label mb-1">Từ khoá</label>
                            <input type="text" name="keyword" id="system-tech-search" class="form-control"
                                value="{{ request('keyword') }}" placeholder="Mã lỗi / Sự cố / Chi nhánh / KTV">
                        </div>
                        <div class="col-md-3">
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
                        <div class="col-md-3">
                            <label for="branch_name" class="form-label mb-1">Tên cơ sở</label>
                            <select class="form-control select2-branch" name="branch_name" id="branch_name" data-field="branch_name">
                                <option value="">-- Chọn cơ sở --</option>
                                @foreach(['mien_bac' => 'Miền Bắc','cici_mien_bac' => 'Cici Miền Bắc', 'mien_nam' => 'Miền Nam','cici_mien_nam' => 'Cici Miền Nam'] as $region => $label)
                                    @if(!empty($stores[$region]))
                                        <optgroup label="{{ $label }}">
                                            @foreach($stores[$region] as $store)
                                                <option value="{{ $store['name'] }}" data-code="{{ $store['code'] }}"
                                                    @selected(request('branch_name') == $store['name'])>
                                                    {{ $store['name'] }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endif
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

    @php
        $tabs = [
            [
                'id' => 'tab-all',
                'label' => 'Tất cả',
                'badge' => ['class' => 'bg-secondary', 'count' => $totalCount],
                'requests' => $allRequests,
            ],
            [
                'id' => 'tab-processing',
                'label' => 'Đang xử lý',
                'badge' => ['class' => 'bg-warning text-dark', 'count' => $processingCount],
                'requests' => $processingRequests,
            ],
            [
                'id' => 'tab-completed',
                'label' => 'Nghiệm thu',
                'badge' => ['class' => 'bg-success', 'count' => $completedCount],
                'requests' => $completedRequests,
            ],
        ];
    @endphp

    <ul class="nav nav-tabs mb-3 flex-nowrap overflow-auto" id="systemTabs">
        @foreach ($tabs as $idx => $tab)
            <li class="nav-item">
                <button class="nav-link{{ $idx === 0 ? ' active' : '' }}" data-bs-toggle="tab" data-bs-target="#{{ $tab['id'] }}">
                    {{ $tab['label'] }}
                    <span class="badge {{ $tab['badge']['class'] }} ms-1">{{ number_format($tab['badge']['count']) }}</span>
                </button>
            </li>
        @endforeach
    </ul>

    <div class="tab-content">
        @foreach ($tabs as $idx => $tab)
            <div class="tab-pane fade{{ $idx === 0 ? ' show active' : '' }}" id="{{ $tab['id'] }}">
                @include('system.partials.new_table', [
                    'items' => $tab['requests'],
                    'stores' => $stores,
                ])
            </div>
        @endforeach
    </div>

    {{-- Modal --}}
    @include('system.modals.modal')
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
    </script>
</x-app-layout>