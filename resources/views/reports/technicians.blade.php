<x-app-layout>
    <x-slot name="header">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <div class="d-flex justify-content-between mb-3">
            <h3>Báo cáo SLA theo kỹ thuật viên</h3>
            <div class="d-flex gap-2 align-items-center">
                @php
                    $fromDate = request('from-date', \Carbon\Carbon::now()->startOfMonth()->format('Y-m-d'));
                    $toDate = request('to-date', \Carbon\Carbon::now()->endOfMonth()->format('Y-m-d'));
                @endphp
                <input type="date" id="from-date" class="form-control" style="width: 180px"
                    value="{{ $fromDate }}">
                <input type="date" id="to-date" class="form-control" style="width: 180px"
                    value="{{ $toDate }}">
                <a href="{{ route('reports.technicians') }}" class="btn btn-outline-secondary">
                    Bỏ lọc
                </a>
                @can('export excel tech')
                    <a href="{{ route('reports.technician-export', [
                        'from-date' => $fromDate,
                        'to-date' => $toDate
                    ]) }}" class="btn btn-outline-success">
                        Xuất báo cáo
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-4">
            <small class="text-muted">Ghi chú: <i><b>ĐM</b>: Định mức</i>, <i><b>YC</b>: Tổng số yêu cầu</i>, <i><b>CH</b>: Cửa hàng</i>
            </small>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-report">
                <thead class="table-dark align-middle text-center">
                    <tr>
                        <th rowspan="2">STT</th>
                        <th rowspan="2">Kỹ thuật viên</th>
                        <th rowspan="2">CH phụ trách</th>
                        <th rowspan="2">ĐM/ngày</th>
                        <th rowspan="2">ĐM/tháng</th>
                        <th colspan="2">Tổng yêu cầu</th>
                        <th colspan="3">Đúng hạn</th>
                        <th colspan="2">Trễ hạn</th>
                        <th colspan="3">Chất lượng</th>
                        <th colspan="2">Không đạt</th>
                    </tr>
                    <tr>
                        <th>SL</th>
                        <th>%/ĐM</th>
                        <th>Đạt</th>
                        <th>%/ĐM</th>
                        <th>%/YC</th>
                        <th>SL</th>
                        <th>%/YC</th>
                        <th>Đạt</th>
                        <th>%/ĐM</th>
                        <th>%/YC</th>
                        <th>SL</th>
                        <th>%/YC</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $index => $item)
                        <tr class="align-middle">
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $item->technician_name }}</strong>
                            </td>
                            @php
                                $inputs = [
                                    ['store_count', $item->store_count, 90],
                                    ['daily_target', $item->daily_target, 100],
                                    ['monthly_target', $item->monthly_target, 100]
                                ];
                            @endphp
                            @foreach($inputs as [$field, $value, $width])
                                <td style="width:{{ $width }}px">
                                    <input
                                        type="number"
                                        min="0"
                                        class="form-control form-control-sm inline-target"
                                        data-tech="{{ $item->technician_name }}"
                                        data-field="{{ $field }}"
                                        value="{{ $value }}">
                                </td>
                            @endforeach
                            <td class="text-center fw-bold">{{ $item->total_completed }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary">
                                    {{ $item->completion_percent }}%
                                </span>
                            </td>
                            <td class="text-center text-success fw-bold">{{ $item->dung_han_count }}</td>
                            <td class="text-center">{{ $item->dung_han_dm_percent }}%</td>
                            <td class="text-center">{{ $item->dung_han_total_percent }}%</td>
                            <td class="text-center text-danger fw-bold">{{ $item->khong_dung_han_count }}</td>
                            <td class="text-center">{{ $item->khong_dung_han_percent }}%</td>
                            <td class="text-center text-success fw-bold">{{ $item->quality_pass_count }}</td>
                            <td class="text-center">{{ $item->quality_pass_dm_percent }}%</td>
                            <td class="text-center">{{ $item->quality_pass_total_percent }}%</td>
                            <td class="text-center text-danger fw-bold">{{ $item->quality_fail_count }}</td>
                            <td class="text-center">{{ $item->quality_fail_total_percent }}%</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="17" class="text-center">
                                Không có dữ liệu
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="5" class="text-end">
                            Tổng cộng
                        </td>
                        <td class="text-center">
                            {{ $requests->sum('total_completed') }}
                        </td>
                        <td></td>
                        <td class="text-center text-success">
                            {{ $requests->sum('dung_han_count') }}
                        </td>
                        <td></td>
                        <td></td>
                        <td class="text-center text-danger">
                            {{ $requests->sum('khong_dung_han_count') }}
                        </td>
                        <td></td>
                        <td class="text-center text-success">
                            {{ $requests->sum('quality_pass_count') }}
                        </td>
                        <td></td>
                        <td></td>
                        <td class="text-center text-danger">
                            {{ $requests->sum('quality_fail_count') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @include('reports.modals.export_by_date')
</x-app-layout>