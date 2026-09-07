@php
    // Data
    $rows = $requests ?? collect();

    // Danh sách các trường cần tính tổng & kiểu
    $sumFields = [

        // 5-7
        'store' => [
            'store_count',
            'int',
        ],

        'daily_target' => [
            'daily_target',
            'float',
        ],

        'monthly_target' => [
            'monthly_target',
            'float',
        ],

        // 8
        'completed' => [
            'total_completed',
            'float',
        ],

        // 9-12: Phân loại công việc
        'onsite_in' => [
            'onsite_in_work_count',
            'float',
        ],

        'onsite_off' => [
            'onsite_off_work_count',
            'float',
        ],

        'online_in' => [
            'online_in_work_count',
            'float',
        ],

        'online_off' => [
            'online_off_work_count',
            'float',
        ],

        // Quy đổi
        'quy_doi' => [
            'quy_doi_count',
            'float',
        ],

        // Thời gian
        'onsite_in_ontime' => [
            'onsite_in_work_on_time_quality_count',
            'float',
        ],

        'onsite_in_late' => [
            'onsite_in_work_late_count',
            'float',
        ],

        'onsite_off_ontime' => [
            'onsite_off_work_on_time_quality_count',
            'float',
        ],

        'onsite_off_late' => [
            'onsite_off_work_late_count',
            'float',
        ],

        'online_in_ontime' => [
            'online_in_work_on_time_quality_count',
            'float',
        ],

        'online_in_late' => [
            'online_in_work_late_count',
            'float',
        ],

        'online_off_ontime' => [
            'online_off_work_on_time_quality_count',
            'float',
        ],

        'online_off_late' => [
            'online_off_work_late_count',
            'float',
        ],

        // Tổng không đạt thời gian
        'late' => [
            'total_late_count',
            'float',
        ],

        // Chất lượng
        'onsite_qual_pass' => [
            'onsite_quality_pass_count',
            'float',
        ],

        'onsite_qual_fail' => [
            'onsite_quality_fail_count',
            'float',
        ],

        'online_in_qual_pass' => [
            'online_in_work_quality_pass_count',
            'float',
        ],

        'online_in_qual_fail' => [
            'online_in_work_quality_fail_count',
            'float',
        ],

        'online_off_qual_pass' => [
            'online_off_work_quality_pass_count',
            'float',
        ],

        'online_off_qual_fail' => [
            'online_off_work_quality_fail_count',
            'float',
        ],

        // Tổng không đạt chất lượng
        'quality_fail' => [
            'quality_fail_count',
            'float',
        ],
    ];

    // Tính tổng các trường trong 1 lần lặp duy nhất qua dữ liệu
    $totals = [];

    foreach ($sumFields as $key => [$field, $type]) {
        $totals[$key] = 0;
    }

    foreach ($rows as $item) {
        foreach ($sumFields as $key => [$field, $type]) {
            $val = $item->$field ?? 0;

            $totals[$key] += $type === 'int'
                ? (int) $val
                : (float) $val;
        }
    }

    // Tổng tỷ lệ (0 nếu mẫu số = 0)
    $totalCompletionPercent = $totals['monthly_target'] > 0
        ? round($totals['completed'] / $totals['monthly_target'] * 100, 2) : 0;
    $totalQuyDoiPercent = $totals['monthly_target'] > 0
        ? round($totals['quy_doi'] / $totals['monthly_target'] * 100, 2) : 0;
    $totalLatePercent = $totals['completed'] > 0
        ? round($totals['late'] / $totals['completed'] * 100, 2) : 0;
    $totalQualityFailPercent = $totals['completed'] > 0
        ? round($totals['quality_fail'] / $totals['completed'] * 100, 2) : 0;

    // Filter
    $fromDate = request('from-date', now()->startOfMonth()->format('Y-m-d'));
    $toDate = request('to-date', now()->endOfMonth()->format('Y-m-d'));
    $fromDateCompleted = request('from-date-completed', '');
    $toDateCompleted = request('to-date-completed', '');
@endphp

@if ($rows->isNotEmpty())
    <x-app-layout>
        <x-slot name="header">
            <meta name="csrf-token" content="{{ csrf_token() }}">
            <div class="report-header">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h3 class="mb-1">Báo cáo SLA theo kỹ thuật viên</h3>
                        <small class="text-muted">Theo dõi định mức, thời gian xử lý và chất lượng công việc</small>
                    </div>
                    <form method="GET" action="{{ route('reports.technicians_system') }}" class="report-filter">
                        @foreach([
                                ['from-date', 'Từ ngày', $fromDate],
                                ['to-date', 'Đến ngày', $toDate],
                                ['from-date-completed', 'HT từ', $fromDateCompleted],
                                ['to-date-completed', 'HT đến', $toDateCompleted]
                            ] as [$id, $label, $val])
                            <div class="filter-item">
                                <label for="{{ $id }}">{{ $label }}</label>
                                <input type="date" id="{{ $id }}" name="{{ $id }}" class="form-control form-control-sm"
                                    value="{{ $val }}">
                            </div>
                        @endforeach
                        <div class="filter-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-funnel"></i> Lọc
                            </button>
                            <a href="{{ route('reports.technicians_system') }}" class="btn btn-outline-secondary">Bỏ lọc</a>
                            @can('export excel tech')
                                <button type="button" class="btn btn-outline-success" data-bs-toggle="modal"
                                    data-bs-target="#exportsSystemModal">
                                    <i class="fas fa-file-excel"></i>
                                    Xuất báo cáo
                                </button>
                            @endcan
                        </div>
                    </form>
                </div>
            </div>
        </x-slot>

        <div class="py-3">
            <div class="report-legend mb-2">
                @foreach([
                        ['ĐM', 'Định mức'],
                        ['YC', 'Yêu cầu'],
                        ['CH', 'Cửa hàng'],
                        ['CL', 'Chất lượng'],
                        ['HT', 'Hoàn thành'],
                        ['ĐH', 'Đúng hạn'],
                        ['Trễ', 'Không đạt thời gian'],
                    ] as [$abbr, $desc])
                    <span>
                        <strong>{{ $abbr }}</strong> {{ $desc }}
                    </span>
                @endforeach
            </div>
            <div class="report-wrapper">
                <table class="table table-bordered table-striped report-tech-table">
                    <colgroup>
                        @php
                            $colWidths = [
                                // 1-3
                                45,   // STT
                                80,   // Mã NV
                                150,  // Kỹ thuật viên

                                // 4-7
                                100,  // Chức danh
                                70,   // CH phụ trách
                                75,   // ĐM ngày
                                75,   // ĐM tháng

                                // 8
                                75,   // Tổng YC

                                // 9-12: Phân loại công việc
                                90,   // Onsite trong giờ
                                90,   // Onsite ngoài giờ
                                90,   // Online trong giờ
                                90,   // Online ngoài giờ

                                // 13-15: Tỷ lệ hoàn thành
                                80,   // % / ĐM
                                95,   // Số công việc quy đổi
                                90,   // % / ĐM quy đổi

                                // 16-27: Thời gian
                                105,  // Onsite trong giờ đúng hạn + CL
                                90,   // Onsite trong giờ trễ
                                105,  // Onsite trong giờ chưa đáp ứng

                                105,  // Onsite ngoài giờ đúng hạn + CL
                                90,   // Onsite ngoài giờ trễ
                                105,  // Onsite ngoài giờ chưa đáp ứng

                                105,  // Online trong giờ đúng hạn + CL
                                90,   // Online trong giờ trễ
                                105,  // Online trong giờ chưa đáp ứng

                                105,  // Online ngoài giờ đúng hạn + CL
                                90,   // Online ngoài giờ trễ
                                105,  // Online ngoài giờ chưa đáp ứng

                                // 28-29: Quá hạn
                                80,   // Tổng trễ
                                80,   // % quá hạn

                                // 30-35: Chất lượng
                                90,   // Onsite đạt CL
                                90,   // Onsite không đạt CL
                                100,  // Online trong giờ đạt CL
                                100,  // Online trong giờ không đạt CL
                                105,  // Online ngoài giờ đạt CL
                                105,  // Online ngoài giờ không đạt CL

                                // 36-37
                                90,   // Tổng không đạt CL
                                90,   // % không đạt CL
                            ];
                        @endphp

                        @foreach ($colWidths as $width)
                            <col style="width:{{ $width }}px">
                        @endforeach
                    </colgroup>

                    <thead class="table-dark">
                        <tr>
                            {{-- 1-3 --}}
                            <th rowspan="2" class="sticky-1">STT</th>
                            <th rowspan="2" class="sticky-2">Mã NV</th>
                            <th rowspan="2" class="sticky-3">Kỹ thuật viên</th>

                            {{-- 4-7 --}}
                            <th rowspan="2">Chức danh</th>
                            <th rowspan="2">CH<br>phụ trách</th>
                            <th rowspan="2">ĐM<br>ngày</th>
                            <th rowspan="2">ĐM<br>tháng</th>

                            {{-- 8 --}}
                            <th rowspan="2">Tổng<br>YC</th>

                            {{-- 9-12 --}}
                            <th colspan="4" class="group-header">
                                Phân loại công việc
                            </th>

                            {{-- 13-15 --}}
                            <th colspan="3" class="group-header">
                                Tỷ lệ hoàn thành
                            </th>

                            {{-- 16-27 --}}
                            <th colspan="12" class="group-header">
                                Thời gian
                            </th>

                            {{-- 28-29 --}}
                            <th colspan="2" class="group-header">
                                Quá hạn
                            </th>

                            {{-- 30-35 --}}
                            <th colspan="6" class="group-header">
                                Chất lượng
                            </th>

                            {{-- 36-37 --}}
                            <th colspan="2" class="group-header">
                                Không đạt CL
                            </th>
                        </tr>

                        <tr>
                            {{-- 9-12: Phân loại công việc --}}

                            <th>
                                Onsite<br>trong giờ
                            </th>

                            <th>
                                Onsite<br>ngoài giờ
                            </th>

                            <th>
                                Online<br>trong giờ
                            </th>

                            <th>
                                Online<br>ngoài giờ
                            </th>

                            {{-- 13-15: Tỷ lệ hoàn thành --}}

                            <th class="percent-header percent-header-completion">
                                % / ĐM
                            </th>

                            <th>
                                Số việc<br>quy đổi
                            </th>

                            <th class="percent-header percent-header-converted">
                                % / ĐM<br>quy đổi
                            </th>

                            {{-- 16-27: Thời gian --}}

                            {{-- Onsite trong giờ --}}
                            <th>
                                Onsite trong giờ<br>
                                đúng hạn + CL
                            </th>

                            <th>
                                Onsite trong giờ<br>
                                trễ
                            </th>

                            <th>
                                Onsite trong giờ<br>
                                chưa đáp ứng
                            </th>

                            {{-- Onsite ngoài giờ --}}
                            <th>
                                Onsite ngoài giờ<br>
                                đúng hạn + CL
                            </th>

                            <th>
                                Onsite ngoài giờ<br>
                                trễ
                            </th>

                            <th>
                                Onsite ngoài giờ<br>
                                chưa đáp ứng
                            </th>

                            {{-- Online trong giờ --}}
                            <th>
                                Online trong giờ<br>
                                đúng hạn + CL
                            </th>

                            <th>
                                Online trong giờ<br>
                                trễ
                            </th>

                            <th>
                                Online trong giờ<br>
                                chưa đáp ứng
                            </th>

                            {{-- Online ngoài giờ --}}
                            <th>
                                Online ngoài giờ<br>
                                đúng hạn + CL
                            </th>

                            <th>
                                Online ngoài giờ<br>
                                trễ
                            </th>

                            <th>
                                Online ngoài giờ<br>
                                chưa đáp ứng
                            </th>

                            {{-- 28-29: Quá hạn --}}

                            <th>
                                Tổng<br>trễ
                            </th>

                            <th class="percent-header percent-header-late">
                                %<br>quá hạn
                            </th>

                            {{-- 30-35: Chất lượng --}}

                            <th>
                                Onsite<br>đạt CL
                            </th>

                            <th>
                                Onsite<br>không đạt CL
                            </th>

                            <th>
                                Online trong giờ<br>đạt CL
                            </th>

                            <th>
                                Online trong giờ<br>không đạt CL
                            </th>

                            <th>
                                Online ngoài giờ<br>đạt CL
                            </th>

                            <th>
                                Online ngoài giờ<br>không đạt CL
                            </th>

                            {{-- 36-37 --}}

                            <th>
                                Tổng<br>không đạt CL
                            </th>

                            <th class="percent-header percent-header-quality">
                                %<br>không đạt CL
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($rows as $index => $item)
                            <tr>

                                {{-- 1: STT --}}
                                <td class="sticky-1">
                                    {{ $index + 1 }}
                                </td>

                                {{-- 2: Mã NV --}}
                                <td class="sticky-2">
                                    <strong>
                                        {{ $item->technician_code ?? '' }}
                                    </strong>
                                </td>

                                {{-- 3: Họ tên --}}
                                <td class="sticky-3">
                                    <span class="tech-name" title="{{ $item->technician_email ?? '' }}">
                                        <strong>
                                            {{ $item->technician_name ?? 'N/A' }}
                                        </strong>
                                    </span>
                                </td>

                                {{-- 4: Vị trí --}}
                                <td>
                                    {{ $item->technician_position ?? 'N/A' }}
                                </td>

                                {{-- 5-7: Target --}}
                                @foreach([
                                        'store_count',
                                        'daily_target',
                                        'monthly_target'
                                    ] as $field)

                                    <td class="target-cell">

                                        @can('can edit report')

                                            <input type="number" min="0" class="form-control form-control-sm inline-target-system"
                                                data-tech="{{ $item->technician_name }}" data-field="{{ $field }}"
                                                value="{{ $item->$field ?? 0 }}">

                                        @else

                                            <span>
                                                {{ $item->$field ?? 0 }}
                                            </span>

                                        @endcan

                                    </td>

                                @endforeach

                                {{-- 8: Tổng số vụ --}}
                                <td class="fw-semibold">
                                    {{ $item->total_completed ?? 0 }}
                                </td>

                                {{-- 9: Onsite trong giờ --}}
                                <td>
                                    {{ $item->onsite_in_work_count ?? 0 }}
                                </td>

                                {{-- 10: Onsite ngoài giờ --}}
                                <td>
                                    {{ $item->onsite_off_work_count ?? 0 }}
                                </td>

                                {{-- 11: Online trong giờ --}}
                                <td>
                                    {{ $item->online_in_work_count ?? 0 }}
                                </td>

                                {{-- 12: Online ngoài giờ --}}
                                <td>
                                    {{ $item->online_off_work_count ?? 0 }}
                                </td>

                                {{-- 13: HT/ĐM --}}
                                <td class="percent-cell percent-completion">
                                    <span class="percent-value">
                                        {{ number_format((float) ($item->completion_percent ?? 0), 2) }}%
                                    </span>
                                </td>

                                {{-- 14: Công việc quy đổi --}}
                                <td>
                                    {{ number_format((float) ($item->quy_doi_count ?? 0), 2) }}
                                </td>

                                {{-- 15: HT/ĐM quy đổi --}}
                                <td class="percent-cell percent-completion-converted">
                                    <span class="percent-value">
                                        {{ number_format((float) ($item->completion_quy_doi_percent ?? 0), 2) }}%
                                    </span>
                                </td>

                                {{-- 16: Onsite trong giờ - đúng hạn + CL --}}
                                <td>
                                    {{ $item->onsite_in_work_on_time_quality_count ?? 0 }}
                                </td>

                                {{-- 17: Onsite trong giờ - trễ --}}
                                <td>
                                    {{ $item->onsite_in_work_late_count ?? 0 }}
                                </td>

                                {{-- 18: Onsite trong giờ - chưa đáp ứng --}}
                                <td>
                                    {{ $item->onsite_in_work_not_met_count ?? 0 }}
                                </td>

                                {{-- 19: Onsite ngoài giờ - đúng hạn + CL --}}
                                <td>
                                    {{ $item->onsite_off_work_on_time_quality_count ?? 0 }}
                                </td>

                                {{-- 20: Onsite ngoài giờ - trễ --}}
                                <td>
                                    {{ $item->onsite_off_work_late_count ?? 0 }}
                                </td>

                                {{-- 21: Onsite ngoài giờ - chưa đáp ứng --}}
                                <td>
                                    {{ $item->onsite_off_work_not_met_count ?? 0 }}
                                </td>

                                {{-- 22: Online trong giờ - đúng hạn + CL --}}
                                <td>
                                    {{ $item->online_in_work_on_time_quality_count ?? 0 }}
                                </td>

                                {{-- 23: Online trong giờ - trễ --}}
                                <td>
                                    {{ $item->online_in_work_late_count ?? 0 }}
                                </td>

                                {{-- 24: Online trong giờ - chưa đáp ứng --}}
                                <td>
                                    {{ $item->online_in_work_not_met_count ?? 0 }}
                                </td>

                                {{-- 25: Online ngoài giờ - đúng hạn + CL --}}
                                <td>
                                    {{ $item->online_off_work_on_time_quality_count ?? 0 }}
                                </td>

                                {{-- 26: Online ngoài giờ - trễ --}}
                                <td>
                                    {{ $item->online_off_work_late_count ?? 0 }}
                                </td>

                                {{-- 27: Online ngoài giờ - chưa đáp ứng --}}
                                <td>
                                    {{ $item->online_off_work_not_met_count ?? 0 }}
                                </td>

                                {{-- 28: Tổng trễ --}}
                                <td class="fw-semibold">
                                    {{ $item->total_late_count ?? 0 }}
                                </td>

                                {{-- 29: Quá hạn % --}}
                                <td class="percent-cell percent-late">
                                    <span class="percent-value">
                                        {{ number_format((float) ($item->late_percent ?? 0), 2) }}%
                                    </span>
                                </td>

                                {{-- 30: Onsite đạt CL --}}
                                <td>
                                    {{ $item->onsite_quality_pass_count ?? 0 }}
                                </td>

                                {{-- 31: Onsite không đạt CL --}}
                                <td>
                                    {{ $item->onsite_quality_fail_count ?? 0 }}
                                </td>

                                {{-- 32: Online trong giờ đạt CL --}}
                                <td>
                                    {{ $item->online_in_work_quality_pass_count ?? 0 }}
                                </td>

                                {{-- 33: Online trong giờ không đạt CL --}}
                                <td>
                                    {{ $item->online_in_work_quality_fail_count ?? 0 }}
                                </td>

                                {{-- 34: Online ngoài giờ đạt CL --}}
                                <td>
                                    {{ $item->online_off_work_quality_pass_count ?? 0 }}
                                </td>

                                {{-- 35: Online ngoài giờ không đạt CL --}}
                                <td>
                                    {{ $item->online_off_work_quality_fail_count ?? 0 }}
                                </td>

                                {{-- 36: Tổng không đạt CL --}}
                                <td class="fw-semibold">
                                    {{ $item->quality_fail_count ?? 0 }}
                                </td>

                                {{-- 37: Không đạt CL % --}}
                                <td class="percent-cell percent-quality">
                                    <span class="percent-value">
                                        {{ number_format((float) ($item->quality_fail_percent ?? 0), 2) }}%
                                    </span>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>

                            {{-- 1 --}}
                            <td class="sticky-1">—</td>

                            {{-- 2 --}}
                            <td class="sticky-2">—</td>

                            {{-- 3 --}}
                            <td class="sticky-3">
                                Tổng cộng
                            </td>

                            {{-- 4 --}}
                            <td>—</td>

                            {{-- 5-7 --}}
                            <td>—</td>
                            <td>—</td>
                            <td>—</td>

                            {{-- 8: Tổng --}}
                            <td>
                                {{ number_format($totals['completed'] ?? 0, 0) }}
                            </td>

                            {{-- 9: Onsite trong --}}
                            <td>
                                {{ number_format($totals['onsite_in'] ?? 0, 0) }}
                            </td>

                            {{-- 10: Onsite ngoài --}}
                            <td>
                                {{ number_format($totals['onsite_off'] ?? 0, 0) }}
                            </td>

                            {{-- 11: Online trong --}}
                            <td>
                                {{ number_format($totals['online_in'] ?? 0, 0) }}
                            </td>

                            {{-- 12: Online ngoài --}}
                            <td>
                                {{ number_format($totals['online_off'] ?? 0, 0) }}
                            </td>

                            {{-- 13: HT/ĐM --}}
                            <td>—</td>

                            {{-- 14: Quy đổi --}}
                            <td>
                                {{ number_format($totals['quy_doi'] ?? 0, 2) }}
                            </td>

                            {{-- 15: HT/ĐM quy đổi --}}
                            <td>—</td>

                            {{-- 16: Onsite trong đúng hạn + CL --}}
                            <td>
                                {{ number_format($totals['onsite_in_ontime'] ?? 0, 0) }}
                            </td>

                            {{-- 17: Onsite trong trễ --}}
                            <td>
                                {{ number_format($totals['onsite_in_late'] ?? 0, 0) }}
                            </td>

                            {{-- 18: Onsite trong chưa đáp ứng --}}
                            <td>
                                {{ number_format($totals['onsite_in_not_met'] ?? 0, 0) }}
                            </td>

                            {{-- 19: Onsite ngoài đúng hạn + CL --}}
                            <td>
                                {{ number_format($totals['onsite_off_ontime'] ?? 0, 0) }}
                            </td>

                            {{-- 20: Onsite ngoài trễ --}}
                            <td>
                                {{ number_format($totals['onsite_off_late'] ?? 0, 0) }}
                            </td>

                            {{-- 21: Onsite ngoài chưa đáp ứng --}}
                            <td>
                                {{ number_format($totals['onsite_off_not_met'] ?? 0, 0) }}
                            </td>

                            {{-- 22: Online trong đúng hạn + CL --}}
                            <td>
                                {{ number_format($totals['online_in_ontime'] ?? 0, 0) }}
                            </td>

                            {{-- 23: Online trong trễ --}}
                            <td>
                                {{ number_format($totals['online_in_late'] ?? 0, 0) }}
                            </td>

                            {{-- 24: Online trong chưa đáp ứng --}}
                            <td>
                                {{ number_format($totals['online_in_not_met'] ?? 0, 0) }}
                            </td>

                            {{-- 25: Online ngoài đúng hạn + CL --}}
                            <td>
                                {{ number_format($totals['online_off_ontime'] ?? 0, 0) }}
                            </td>

                            {{-- 26: Online ngoài trễ --}}
                            <td>
                                {{ number_format($totals['online_off_late'] ?? 0, 0) }}
                            </td>

                            {{-- 27: Online ngoài chưa đáp ứng --}}
                            <td>
                                {{ number_format($totals['online_off_not_met'] ?? 0, 0) }}
                            </td>

                            {{-- 28: Tổng trễ --}}
                            <td class="fw-semibold">
                                {{ number_format($totals['late'] ?? 0, 0) }}
                            </td>

                            {{-- 29: % quá hạn --}}
                            <td>—</td>

                            {{-- 30: Onsite đạt CL --}}
                            <td>
                                {{ number_format($totals['onsite_qual_pass'] ?? 0, 0) }}
                            </td>

                            {{-- 31: Onsite không đạt CL --}}
                            <td>
                                {{ number_format($totals['onsite_qual_fail'] ?? 0, 0) }}
                            </td>

                            {{-- 32: Online trong đạt CL --}}
                            <td>
                                {{ number_format($totals['online_in_qual_pass'] ?? 0, 0) }}
                            </td>

                            {{-- 33: Online trong không đạt CL --}}
                            <td>
                                {{ number_format($totals['online_in_qual_fail'] ?? 0, 0) }}
                            </td>

                            {{-- 34: Online ngoài đạt CL --}}
                            <td>
                                {{ number_format($totals['online_off_qual_pass'] ?? 0, 0) }}
                            </td>

                            {{-- 35: Online ngoài không đạt CL --}}
                            <td>
                                {{ number_format($totals['online_off_qual_fail'] ?? 0, 0) }}
                            </td>

                            {{-- 36: Tổng không đạt CL --}}
                            <td class="fw-semibold">
                                {{ number_format($totals['quality_fail'] ?? 0, 0) }}
                            </td>

                            {{-- 37: % không đạt CL --}}
                            <td>—</td>

                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @include('reports.modals.export_system')

        <style>
            .report-header {
                width: 100%;
            }

            .report-filter {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                gap: 6px;
            }

            .filter-item {
                width: 125px;
            }

            .filter-item label {
                display: block;
                margin-bottom: 2px;
                font-size: 12px;
                color: #6c757d;
            }

            .filter-actions {
                display: flex;
                gap: 4px;
            }

            .report-legend {
                display: flex;
                flex-wrap: wrap;
                gap: 14px;
                padding: 7px 10px;
                border-left: 3px solid #6c757d;
                background: #f8f9fa;
                font-size: 14px;
                color: #6c757d;
            }

            .report-legend strong {
                color: #212529;
            }

            .report-wrapper {
                position: relative;
                width: 100%;
                max-width: 100%;
                overflow-x: auto;
                overflow-y: visible;
                border: 1px solid #dee2e6;
                background: #fff;
                isolation: isolate;
            }

            .report-tech-table {
                width: max-content;
                min-width: 1900px;
                margin: 0;
                table-layout: fixed;
                border-collapse: separate;
                border-spacing: 0;
                font-size: 14px;
            }

            .report-tech-table th,
            .report-tech-table td {
                padding: 7px;
                vertical-align: middle;
                text-align: center;
                font-size: 14px;
                line-height: 1.3;
                white-space: normal;
                word-break: normal;
                background-clip: padding-box;
            }

            .report-tech-table thead th {
                position: sticky;
                top: 0;
                min-height: 42px;
                padding: 8px 7px;
                font-size: 15px;
                font-weight: 600;
                line-height: 1.25;
                vertical-align: middle;
                z-index: 20;
            }

            .report-tech-table thead .group-header {
                font-size: 16px;
                font-weight: 700;
                white-space: nowrap;
                line-height: 1.2;
                border-bottom: 2px solid #6c757d;
            }

            .report-tech-table thead tr:nth-child(2) th {
                font-size: 15px;
                line-height: 1.25;
                padding: 8px 6px;
            }

            .report-tech-table .sticky-1 {
                position: sticky;
                left: 0;
                width: 45px;
                min-width: 45px;
                max-width: 45px;
                background: #fff !important;
                z-index: 40;
                box-shadow: 1px 0 0 #dee2e6;
            }

            .report-tech-table .sticky-2 {
                position: sticky;
                left: 45px;
                width: 80px;
                min-width: 80px;
                max-width: 80px;
                background: #fff !important;
                z-index: 40;
                box-shadow: 1px 0 0 #dee2e6;
            }

            .report-tech-table .sticky-3 {
                position: sticky;
                left: 125px;
                width: 150px;
                min-width: 150px;
                max-width: 150px;
                background: #fff !important;
                z-index: 41;
                border-right: 2px solid #6c757d !important;
                box-shadow: 4px 0 7px rgba(0, 0, 0, 0.14);
            }

            .report-tech-table thead .sticky-1,
            .report-tech-table thead .sticky-2,
            .report-tech-table thead .sticky-3 {
                background: #212529 !important;
                color: #fff;
                z-index: 60;
            }

            .report-tech-table tfoot .sticky-1,
            .report-tech-table tfoot .sticky-2,
            .report-tech-table tfoot .sticky-3 {
                background: #f8f9fa !important;
                z-index: 50;
            }

            .report-tech-table tbody td:not(.sticky-1):not(.sticky-2):not(.sticky-3),
            .report-tech-table tfoot td:not(.sticky-1):not(.sticky-2):not(.sticky-3) {
                position: relative;
                z-index: 1;
            }

            .report-tech-table tbody tr:hover td {
                background-color: #f8f9fa;
            }

            .report-tech-table tbody tr:hover .sticky-1,
            .report-tech-table tbody tr:hover .sticky-2,
            .report-tech-table tbody tr:hover .sticky-3 {
                background-color: #eef1f4 !important;
            }

            .tech-name {
                display: block;
                max-width: 140px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                cursor: default;
                font-size: 14px;
            }

            .target-cell {
                padding: 5px !important;
            }

            .inline-target-system {
                width: 62px !important;
                min-width: 62px !important;
                height: 31px;
                padding: 3px 5px !important;
                text-align: center;
                font-size: 14px;
            }

            .percent-cell {
                padding: 5px 6px !important;
                white-space: nowrap !important;
                text-align: center !important;
                vertical-align: middle !important;
            }

            .percent-value {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 68px;
                min-height: 29px;
                padding: 4px 7px;
                border-radius: 5px;
                font-size: 14px;
                font-weight: 700;
                line-height: 1.2;
                white-space: nowrap;
            }

            .percent-completion {
                background-color: #f0f7ff !important;
            }

            .percent-completion .percent-value {
                background-color: #e7f1ff;
                border: 1px solid #b8d4f8;
                color: #0d6efd;
            }

            .percent-completion-converted {
                background-color: #f5f1ff !important;
            }

            .percent-completion-converted .percent-value {
                background-color: #eee9ff;
                border: 1px solid #c9bdf5;
                color: #6f42c1;
            }

            .percent-late {
                background-color: #fff8e6 !important;
            }

            .percent-late .percent-value {
                background-color: #fff3cd;
                border: 1px solid #ffda6a;
                color: #946200;
            }

            .percent-quality {
                background-color: #fff0f0 !important;
            }

            .percent-quality .percent-value {
                background-color: #f8d7da;
                border: 1px solid #f1aeb5;
                color: #b02a37;
            }

            .report-tech-table thead .percent-header {
                font-size: 15px !important;
                font-weight: 700;
                line-height: 1.2;
            }

            .report-tech-table thead .percent-header-completion {
                background-color: #0d6efd !important;
            }

            .report-tech-table thead .percent-header-converted {
                background-color: #6f42c1 !important;
            }

            .report-tech-table thead .percent-header-late {
                background-color: #946200 !important;
            }

            .report-tech-table thead .percent-header-quality {
                background-color: #b02a37 !important;
            }

            .report-tech-table tfoot td {
                background-color: #f8f9fa;
                font-size: 14px;
                font-weight: 700;
                white-space: nowrap;
            }

            .empty-cell {
                padding: 20px !important;
                font-size: 14px !important;
                color: #6c757d;
            }

            .report-tech-table tbody tr {
                height: 42px;
            }

            @media (max-width: 992px) {
                .report-filter {
                    width: 100%;
                }

                .filter-item {
                    width: 120px;
                }

                .report-tech-table,
                .report-tech-table th,
                .report-tech-table td {
                    font-size: 14px;
                }

                .report-tech-table thead .group-header {
                    font-size: 15px;
                }
            }
        </style>
    </x-app-layout>
@else
    <div class="py-3">
        <div class="report-legend mb-2">
            @foreach([
                    ['ĐM', 'Định mức'],
                    ['YC', 'Yêu cầu'],
                    ['CH', 'Cửa hàng'],
                    ['CL', 'Chất lượng'],
                    ['HT', 'Hoàn thành'],
                    ['ĐH', 'Đúng hạn'],
                    ['Trễ', 'Không đạt thời gian'],
                ] as [$abbr, $desc])
                <span>
                    <strong>{{ $abbr }}</strong> {{ $desc }}
                </span>
            @endforeach
        </div>
        <div class="report-wrapper">
            <table class="table table-bordered table-striped report-tech-table">
                <tbody>
                    <tr>
                        <td colspan="29" class="empty-cell">Không có dữ liệu</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endif