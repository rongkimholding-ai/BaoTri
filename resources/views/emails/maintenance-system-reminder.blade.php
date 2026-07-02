<h2>Yêu cầu bảo trì hạ tầng</h2>

<ul style="padding-left:0;list-style:none">
    <li><strong>Mã cơ sở:</strong> {{ $maintenanceRequest->branch_code }}</li>
    <li><strong>Cơ sở:</strong> {{ $maintenanceRequest->branch_name }}</li>
    <li><strong>Sự cố:</strong> {{ $maintenanceRequest->issue_name }}</li>
    <li><strong>Mô tả:</strong> {{ $maintenanceRequest->issue_description }}</li>
    <li><strong>Kỹ thuật viên:</strong> {{ $maintenanceRequest->technician_name }}</li>
    <li><strong>Đề xuất khắc phục:</strong> {{ $maintenanceRequest->solution_description }}</li>
    <li>
        <strong>Yêu cầu hoàn thành:</strong>
        {{
            $maintenanceRequest->standard_completion_time
                ? (collect(config('real_time'))->keyBy('key')[$maintenanceRequest->standard_completion_time]['name']
                    ?? $maintenanceRequest->standard_completion_time)
                : ''
        }}
    </li>
</ul>

<p>
    <a href="{{ route('maintenance-system.index', $maintenanceRequest->id) }}">
        Xem chi tiết yêu cầu bảo trì hạ tầng
    </a>
</p>

<p>Vui lòng xử lý sớm.</p>