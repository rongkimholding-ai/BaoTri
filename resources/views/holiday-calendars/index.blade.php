<x-app-layout :title="$title">
    <x-slot name="header">
        <h2 class="h4 mb-0">{{ $title }}</h2>
    </x-slot>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-end mb-3">
                <a href="{{ route('holiday-calendars.create') }}" class="btn btn-primary">Thêm mới</a>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="mb-3">{{ $holidays->links() }}</div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th width="60">STT</th>
                            <th>Tên ngày lễ</th>
                            <th>Từ ngày</th>
                            <th>Đến ngày</th>
                            <th>Loại</th>
                            <th>Ghi chú</th>
                            <th width="150">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($holidays as $key => $holiday)
                            <tr>
                                <td>{{ ($holidays->currentPage() - 1) * $holidays->perPage() + $loop->iteration }}</td>
                                <td>{{ $holiday->holiday_name }}</td>
                                <td>{{ optional($holiday->start_date)->format('d/m/Y') }}</td>
                                <td>{{ optional($holiday->end_date)->format('d/m/Y') }}</td>
                                <td>
                                    @if($holiday->is_working_day)
                                        <span class="badge bg-success">Làm bù</span>
                                    @else
                                        <span class="badge bg-danger">Nghỉ</span>
                                    @endif
                                </td>
                                <td>{{ $holiday->description }}</td>
                                <td>
                                    <a href="{{ route('holiday-calendars.edit', $holiday) }}" class="btn btn-sm btn-outline-warning">Sửa</a>
                                    <form action="{{ route('holiday-calendars.destroy', $holiday) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Xác nhận xóa?')">Xóa</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">Không có dữ liệu</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">{{ $holidays->links() }}</div>
        </div>
    </div>
</x-app-layout>