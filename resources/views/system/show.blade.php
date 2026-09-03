<x-app-layout :title="$title">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="p-2" style="width: 40%;">
                <h4 class="mb-0">Chi tiết yêu cầu #{{ $maintenanceSystem->id }}</h4>
                <small class="text-muted">
                    {{ $maintenanceSystem->branch_code }} - {{ $maintenanceSystem->branch_name }}
                </small>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center justify-content-end" style="width: 60%;">

                @hasanyrole('technician_system|admin')
                @can('change-system-status')
                    @php
                        // Lấy workflow từ config
                        $workflow = config('maintenance_system.workflow');
                        $statusNamesHt = config('sla_status.names_ht_func');
                        $currentStatus = $maintenanceSystem->status;
                        $nextStatuses = $workflow[$currentStatus] ?? [];
                        $statusBtnList = [
                            'PROCESSING' => 'btn-outline-warning',
                            'CONTINUE_PROCESSING' => "btn-outline-warning",
                            'PENDING' => "btn-outline-info",
                            'PENDING_CONTRACTOR' => "btn-outline-secondary",
                            'WAITING_CONFIRM' => 'btn-outline-success',
                            'REJECTED' => 'btn-outline-danger',
                            'REOPEN' => 'btn-outline-warning',
                            'CONFIRMED' => 'btn-outline-primary',
                            'COMPLETED' => 'btn-outline-success',
                            'LATED' => 'btn-outline-danger',
                        ];
                    @endphp

                    @if(!in_array($currentStatus, ['COMPLETED', 'LATED']))
                        @foreach ($nextStatuses as $nextStatus)
                            @if(
                                    ($currentStatus === 'CONFIRMED' && auth()->user()->hasRole('admin')) ||
                                    ($currentStatus !== 'CONFIRMED')
                                )
                                <a href="javascript:void(0)"
                                    onclick="openMaintenanceModal('status',{{ $maintenanceSystem->id }},'{{ $nextStatus }}')"
                                    class="btn {{ $statusBtnList[$nextStatus] }} me-1">
                                    {{ $statusNamesHt[$nextStatus] ?? str_replace('_', ' ', $nextStatus) }}
                                </a>
                            @endif
                        @endforeach
                    @endif

                @endcan
                @endhasanyrole

                @role('admin')
                @if($maintenanceSystem->status == 'NEW')
                    <a href="javascript:void(0)" onclick="openMaintenanceModal('edit',{{ $maintenanceSystem->id }})"
                        class="btn btn-outline-primary me-1">
                        <i class="bi bi-pencil"></i> Sửa
                    </a>
                @endif
                <a href="javascript:void(0)" class="btn btn-outline-secondary admin-change-status-system-btn"
                    data-bs-toggle="modal" data-bs-target="#changeStatusSystemModal"
                    data-id="{{ $maintenanceSystem->id }}" data-current-status="{{ $maintenanceSystem->status }}">
                    Đổi trạng thái
                </a>
                @endrole

                @can('acceptance-system-task')
                    @if (in_array($maintenanceSystem->status, ['COMPLETED', 'LATED']) && !$maintenanceSystem->is_confirmed)
                        <a href="javascript:void(0)" class="btn btn-outline-info acceptance-system-btn" data-bs-toggle="modal"
                            data-bs-target="#acceptanceSystemModal" data-id="{{ $maintenanceSystem->id }}">
                            Nghiệm thu
                        </a>
                    @endif
                @endcan

                @can('delete data')
                    @hasrole('admin')
                    @if($maintenanceSystem->sla_status == config('sla_status.code.PROCESSING'))
                        <form action="{{ route('maintenance-requests.destroy', $maintenanceSystem->id) }}" method="POST"
                            style="display:inline-block" onsubmit="return confirm('Xóa bản ghi này?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-outline-danger">Xóa</button>
                        </form>
                    @endif
                    @endhasrole
                @endcan

                <a href="{{ route('maintenance-system.index') }}" class="btn btn-secondary">
                    Quay lại
                </a>
            </div>
        </div>

        @include('system.partials.detail')
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
                                if (status == window.slaStatusCodes.WAITING_CONFIRM) {
                                    $('#workType').removeClass('d-none');
                                    $('#imageSystemUploadWrapper').removeClass('d-none');
                                } else {
                                    $('#workType').addClass('d-none');
                                    $('#imageSystemUploadWrapper').addClass('d-none');
                                    $('#completionSystemImages').val('');
                                }

                                // maintenance.js (807-823) logic:
                                // Khi bắt đầu chọn ảnh: disable nút submit
                                $(document).off('click.systemImage').on('click.systemImage', '#completionSystemImages', function () {
                                    window.selectingImages = true;
                                    $('#confirmSystemChangeStatus').prop('disabled', true);
                                });

                                // Khi đã chọn xong ảnh: enable nút submit, log số lượng/size ảnh
                                $(document).off('change.systemImage').on('change.systemImage', '#completionSystemImages', function () {
                                    window.selectingImages = false;
                                    $('#confirmSystemChangeStatus').prop('disabled', false);
                                    const files = this.files;
                                    if (typeof window.sendClientLog === 'function') {
                                        window.sendClientLog({
                                            type: 'image_selected',
                                            image_count: files.length,
                                            total_size: Array.from(files).reduce((t, f) => t + f.size, 0)
                                        });
                                    }
                                });
                                window.initSystemStatusModal();   
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
    </div>
</x-app-layout>