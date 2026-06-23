<h2>Yêu cầu bảo trì đã được mua sắm</h2>

<p>Mã cơ sở: {{ $maintenanceRequest->branch_code }}</p>

<p>Cơ sở: {{ $maintenanceRequest->branch_name }}</p>

<p>Hạng mục: {{ $maintenanceRequest->item_category }}</p>

@php
    $severities = collect(config('severities'));
    $severityLabel = $severities->where('key', $maintenanceRequest->severity)->first()['name'] ?? $maintenanceRequest->severity;
@endphp
<p>Loại sự cố: {{ $severityLabel }}</p>

<p>Sự cố: {{ $maintenanceRequest->issue_description }}</p>

<p>Kỹ thuật viên: {{ $maintenanceRequest->technician_name }}</p>

<p>Đề xuất khắc phục: {{ $maintenanceRequest->solution_description }}</p>

@php
    $realTimeMap = collect(config('real_time'))->keyBy('key');
    $timeName = isset($maintenanceRequest->standard_completion_time) && $maintenanceRequest->standard_completion_time
        ? ($realTimeMap[$maintenanceRequest->standard_completion_time]['name'] ?? $maintenanceRequest->standard_completion_time)
        : '';
@endphp
<p>Yêu cầu hoàn thành: {{ $timeName }}</p>

<p>
    <a href="{{ route('maintenance-requests.show', $maintenanceRequest->id) }}">
        Xem chi tiết yêu cầu bảo trì
    </a>
</p>

<p>Yêu cầu cần được mua sắm bổ sung, vui lòng xử lý sớm.</p>