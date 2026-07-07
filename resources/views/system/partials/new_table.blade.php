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
                                                @can('acceptance-system-task')
                                                    @if (in_array($currentStatus, ['COMPLETED', 'LATED']) && !$item->is_confirmed)
                                                        <li>
                                                            <a href="javascript:void(0)" class="dropdown-item acceptance-system-btn"
                                                                data-bs-toggle="modal" data-bs-target="#acceptanceSystemModal"
                                                                data-id="{{ $item->id }}">
                                                                Nghiệm thu
                                                            </a>
                                                        </li>
                                                    @endif
                                                @endcan
                                                @role('admin')
                                                <li>
                                                    <a href="javascript:void(0)"
                                                        class="dropdown-item"
                                                        onclick="updateActualDuration({{ $item->id }}, this)">
                                                        <i class="bi bi-arrow-repeat"></i> Cập nhật TG thực tế
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="javascript:void(0)"
                                                        class="dropdown-item"
                                                        onclick="setIncludeWeekendTrue({{ $item->id }}, this)">
                                                        <i class="bi bi-calendar-week"></i> Tính cuối tuần
                                                    </a>
                                                </li>
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
                                                    <li>
                                                        <a href="javascript:void(0)"
                                                        class="dropdown-item text-warning admin-update-tech-btn"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#updateTechModal"
                                                        data-action="{{ route('maintenance-system.update-technician-info',['id'=>$item->id]) }}"
                                                        data-id="{{ $item->id }}"
                                                        data-name="{{ $item->technician_name }}"
                                                        data-email="{{ $item->technician_email }}"
                                                        data-mobile="{{ $item->technician_mobile }}">
                                                            <i class="bi bi-tools"></i> Cập nhật kỹ thuật viên
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
                                            <li>
                                                <a href="javascript:void(0)"
                                                class="dropdown-item text-warning admin-update-tech-btn"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateTechModal"
                                                data-action="{{ route('maintenance-system.update-technician-info',['id'=>$item->id]) }}"
                                                data-id="{{ $item->id }}"
                                                data-name="{{ $item->technician_name }}"
                                                data-email="{{ $item->technician_email }}"
                                                data-mobile="{{ $item->technician_mobile }}">
                                                    <i class="bi bi-tools"></i> Cập nhật kỹ thuật viên
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
    <script>
        function updateActualDuration(id, el) {
            if (!confirm('Bạn có chắc muốn cập nhật lại thời gian thực tế?')) return;
            el.disabled = true;

            fetch('/maintenance-system/update-actual-duration/' + id, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data && data.success) {
                    alert('Đã cập nhật thời gian thực tế thành công!');
                    window.location.reload();
                } else {
                    alert((data && data.message) ? data.message : 'Lỗi không xác định!');
                    el.disabled = false;
                }
            })
            .catch(() => {
                alert('Có lỗi xảy ra!');
                el.disabled = false;
            });
        }
        function setIncludeWeekendTrue(id, el) {
            if(!confirm('Bạn có chắc muốn bật tính cả Thứ 7/CN cho bản ghi này?')) {
                return;
            }
            fetch('/maintenance-system/include-weekend/' + id, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Đã bật tính cả Thứ 7/CN thành công.');
                    location.reload();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể cập nhật.'));
                }
            })
            .catch(error => {
                alert('Đã xảy ra lỗi. Vui lòng thử lại.');
            });
        }
    </script>