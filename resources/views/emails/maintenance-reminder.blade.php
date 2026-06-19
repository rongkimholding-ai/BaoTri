<h2>Yêu cầu bảo trì</h2>

<p>Mã cơ sở: {{ $request->branch_code }}</p>

<p>Cơ sở: {{ $request->branch_name }}</p>

<p>Hạng mục: {{ $request->item_category }}</p>

@php
    $severities = collect(config('severities'));
    $severityLabel = $severities->where('key', $request->severity)->first()['name'] ?? $request->severity;
@endphp
<p>Loại sự cố: {{ $severityLabel }}</p>

<p>Sự cố: {{ $request->issue_description }}</p>

<p>Kỹ thuật viên: {{ $request->technician_name }}</p>

<p>Đề xuất khắc phục: {{ $request->solution_description }}</p>

@php
    $realTimeMap = collect(config('real_time'))->keyBy('key');
    $timeName = isset($request->standard_completion_time) && $request->standard_completion_time
        ? ($realTimeMap[$request->standard_completion_time]['name'] ?? $request->standard_completion_time)
        : '';
@endphp
<p>Yêu cầu hoàn thành: {{ $timeName }}</p>

<p>
    <a href="{{ route('maintenance-requests.index', ['id' => $request->id]) }}">
        Xem chi tiết yêu cầu bảo trì (ID: {{ $request->id }})
    </a>
</p>

<p>Vui lòng xử lý sớm.</p>