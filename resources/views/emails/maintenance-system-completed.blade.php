<h2>Yêu cầu bảo trì hạ tầng đã được hỗ trợ</h2>

<ul style="padding-left:0;list-style:none">
    <li><strong>Mã cơ sở:</strong> {{ $maintenanceRequest->branch_code }}</li>
    <li><strong>Cơ sở:</strong> {{ $maintenanceRequest->branch_name }}</li>
    <li><strong>Sự cố:</strong> {{ $maintenanceRequest->issue_name }}</li>
    <li><strong>Mô tả:</strong> {{ $maintenanceRequest->issue_description }}</li>
    <li><strong>Kỹ thuật viên:</strong> {{ $maintenanceRequest->technician_name }}</li>
</ul>

<p>
    <a href="{{ route('maintenance-system.show', $maintenanceRequest->id) }}">
        Xem chi tiết yêu cầu bảo trì hạ tầng
    </a>
</p>

<p>Vui lòng vào xác nhận và nghiệm thu sớm.</p>