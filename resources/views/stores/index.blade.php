<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Quản lý cửa hàng
            </h2>
            @can('create-store')
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createStoreModal">
                    <i class="fa fa-plus"></i> Thêm cửa hàng
                </button>
            </div>
            @endcan
        </div>

        <form method="GET" action="{{ route('stores.index') }}" class="row g-3 mb-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="keyword" value="{{ request('keyword') }}"
                       placeholder="Tên, email, mã, AM, OM, KTV...">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="area">
                    <option value="">-- Tất cả miền --</option>
                    <option value="north" {{ request('area') == 'north' ? 'selected' : '' }}>Miền Bắc</option>
                    <option value="south" {{ request('area') == 'south' ? 'selected' : '' }}>Miền Nam</option>
                </select>
            </div>
            <div class="col-md-auto d-flex gap-2 align-items-center">
                <button class="btn btn-primary" type="submit">
                    <i class="fa fa-search"></i> Tìm kiếm
                </button>
                <a href="{{ route('stores.index') }}" class="btn btn-secondary">
                    Làm mới
                </a>
            </div>
        </form>
    </x-slot>

    <div class="container-fluid py-3">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="40">STT</th>
                                <th>Cửa hàng</th>
                                <th>AM</th>
                                <th>OM</th>
                                <th>KTV</th>
                                <th>Email mua sắm</th>
                                <th width="120">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($stores as $store)
                            <tr>
                                <td class="text-center">
                                    {{ $stores->firstItem() + $loop->index }}
                                </td>
                                <td>
                                    <strong>Mã:</strong> {{ $store->code }}<br>
                                    <strong>Tên:</strong> {{ $store->name }}<br>
                                    <span class="text-muted"><strong>Email:</strong> {{ $store->email }}</span><br>
                                    <span class="badge {{ $store->area === 'north' ? 'bg-info' : 'bg-success' }}">
                                        {{ $store->area === 'north' ? 'Miền Bắc' : 'Miền Nam' }}
                                    </span>
                                    <br>
                                    <span class="text-muted"><strong>Khu vực:</strong> {{ $store->region }}</span>
                                </td>
                                <td>
                                    <div>{{ $store->am_name }}</div>
                                    @if($store->am_email)
                                        <small class="text-muted">{{ $store->am_email }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $store->om_name }}</div>
                                    @if($store->om_email)
                                        <small class="text-muted">{{ $store->om_email }}</small>
                                    @endif
                                </td>
                                <td>{{ $store->technician_name }}</td>
                                <td>{{ $store->muasam_email }}</td>
                                <td>
                                    @can('edit-store')
                                    <button type="button"
                                        class="btn btn-warning btn-sm btn-edit"
                                        data-id="{{ $store->id }}"
                                        data-action="{{ route('stores.update', $store) }}"
                                        data-code="{{ $store->code }}"
                                        data-name="{{ $store->name }}"
                                        data-email="{{ $store->email }}"
                                        data-area="{{ $store->area }}"
                                        data-region="{{ $store->region }}"
                                        data-am_name="{{ $store->am_name }}"
                                        data-am_email="{{ $store->am_email }}"
                                        data-om_name="{{ $store->om_name }}"
                                        data-om_email="{{ $store->om_email }}"
                                        data-technician_name="{{ $store->technician_name }}"
                                        data-muasam_email="{{ $store->muasam_email }}"
                                    >
                                        <i class="fa fa-edit"></i> Sửa
                                    </button>
                                    @endcan
                                    @can('delete-store')
                                    <form action="{{ route('stores.destroy', $store) }}" method="POST" class="d-inline"
                                          onsubmit="return confirm('Xóa cửa hàng này?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-danger btn-sm" type="submit">
                                            <i class="fa fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">
                                    Không có dữ liệu.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-3 d-flex justify-content-end">
                    {{ $stores->links() }}
                </div>
            </div>
        </div>
    </div>

    @include('stores.modals.create')
    @include('stores.modals.edit')

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            document.querySelectorAll('.btn-edit').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const form = document.getElementById('editStoreForm');
                    if (!form) return;
                    // Điền dữ liệu vào form từ data attribute trên nút
                    form.action = btn.dataset.action;
                    form.querySelector('[name=code]').value = btn.dataset.code || '';
                    form.querySelector('[name=name]').value = btn.dataset.name || '';
                    form.querySelector('[name=email]').value = btn.dataset.email || '';
                    form.querySelector('[name=area]').value = btn.dataset.area || '';
                    if (typeof $ !== "undefined") {
                        $(form).find('[name=area]').trigger('change');
                    }
                    form.querySelector('[name=region]').value = btn.dataset.region || '';
                    form.querySelector('[name=am_name]').value = btn.dataset.am_name || '';
                    form.querySelector('[name=am_email]').value = btn.dataset.am_email || '';
                    form.querySelector('[name=om_name]').value = btn.dataset.om_name || '';
                    form.querySelector('[name=om_email]').value = btn.dataset.om_email || '';
                    form.querySelector('[name=technician_name]').value = btn.dataset.technician_name || '';
                    form.querySelector('[name=muasam_email]').value = btn.dataset.muasam_email || '';
                    // Hiện modal sửa
                    if (typeof $ !== "undefined" && $('#editStoreModal').modal) {
                        $('#editStoreModal').modal('show');
                    } else {
                        var modal = document.getElementById('editStoreModal');
                        if (modal && typeof bootstrap !== "undefined") {
                            var modalInstance = bootstrap.Modal.getOrCreateInstance(modal);
                            modalInstance.show();
                        }
                    }
                });
            });

            // Reset form tạo mới khi mở modal
            var createModal = document.getElementById('createStoreModal');
            if (createModal) {
                createModal.addEventListener('show.bs.modal', function () {
                    var form = createModal.querySelector('form');
                    if (form) {
                        form.reset();
                        if (typeof $ !== "undefined") {
                            $(form).find('select').val('').trigger('change');
                        }
                        form.querySelectorAll('.is-invalid').forEach(function (el) {
                            el.classList.remove('is-invalid');
                        });
                        form.querySelectorAll('.invalid-feedback').forEach(function (el) {
                            el.remove();
                        });
                    }
                });
            }
        });
    </script>
</x-app-layout>