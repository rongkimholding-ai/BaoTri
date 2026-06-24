<h2>Yêu cầu bảo trì đã được nghiệm thu</h2>

<ul style="padding-left:0;list-style:none">
    <li><strong>Mã cơ sở:</strong> {{ $maintenanceRequest->branch_code }}</li>
    <li><strong>Cơ sở:</strong> {{ $maintenanceRequest->branch_name }}</li>
    <li><strong>Hạng mục:</strong> {{ $maintenanceRequest->item_category }}</li>
    <li>
        <strong>Loại sự cố:</strong>
        {{
            collect(config('severities'))
                ->firstWhere('key', $maintenanceRequest->severity)['name']
                ?? $maintenanceRequest->severity
        }}
    </li>
    <li><strong>Sự cố:</strong> {{ $maintenanceRequest->issue_description }}</li>
    <li><strong>Kỹ thuật viên:</strong> {{ $maintenanceRequest->technician_name }}</li>
</ul>

<p>
    <a href="{{ route('maintenance-requests.show', $maintenanceRequest->id) }}">
        Xem chi tiết yêu cầu bảo trì
    </a>
</p>

<p>Yêu cầu đã được nghiệm thu.</p>