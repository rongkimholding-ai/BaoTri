<x-app-layout>
    <x-slot name="header">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="d-flex justify-content-between mb-3">
            <h3>Báo cáo SLA theo kỹ thuật viên</h3>
            <div class="d-flex gap-2 align-items-center">
                <input type="month" id="month-filter" class="form-control" style="width: 180px"
                    value="{{ request('month', now()->format('Y-m')) }}">
                @can('export excel tech')
                    <a href="{{ route('reports.technician-export', [
                        'month' => request('month', now()->format('Y-m'))
                        ]) }}" class="btn btn-success">
                        Xuất Excel
                    </a>
                    <button
                        type="button"
                        class="btn btn-primary"
                        data-bs-toggle="modal"
                        data-bs-target="#exportModal">
                        <i class="fas fa-file-excel"></i>
                        Báo cáo theo ngày
                    </button>
                @endcan
            </div>
        </div>
    </x-slot>


    <div class="py-4">
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>STT</th>
                        <th>Kỹ thuật viên</th>
                        <th>Số CH phụ trách</th>
                        <th>Định mức sửa/ngày</th>
                        <th>Định mức sửa/tháng</th>
                        <th>Tổng yêu cầu</th>
                        <th>Vượt định mức</th>
                        <th>Đúng hạn</th>
                        <th>Không đúng hạn</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($requests as $index => $item)
                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>{{ $item->technician_name }}</td>

                            <td>
                                <input type="number" class="form-control inline-target"
                                    data-tech="{{ $item->technician_name }}" data-field="store_count"
                                    value="{{ $item->store_count }}">
                            </td>

                            <td>
                                <input type="number" class="form-control inline-target"
                                    data-tech="{{ $item->technician_name }}" data-field="daily_target"
                                    value="{{ $item->daily_target }}">
                            </td>

                            <td>
                                <input type="number" class="form-control inline-target"
                                    data-tech="{{ $item->technician_name }}" data-field="monthly_target"
                                    value="{{ $item->monthly_target }}">
                            </td>

                            <td class="total-cell">
                                <strong>{{ $item->total }}</strong>
                            </td>

                            <th class="vuot-dinh-muc-cell">{{ $item->vuot_dinh_muc }}</th>

                            <td>
                                {{ $item->dung_han_count }}/{{ $item->total }}
                                ({{ $item->dung_han_percent }}%)
                            </td>

                            <td>
                                {{ $item->con_lai_count }}/{{ $item->total }}
                                ({{ $item->con_lai_percent }}%)
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center">
                                Không có dữ liệu
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="5">Tổng cộng</th>
                        <th>{{ $requests->sum('total') }}</th>
                        <th>{{ $requests->sum('vuot_dinh_muc') }}</th>
                        <th>{{ $requests->sum('dung_han_count') }}</th>
                        <th>{{ $requests->sum('con_lai_count') }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    @include('reports.modals.export_by_date')
</x-app-layout>