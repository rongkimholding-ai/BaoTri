<h2>Yêu cầu bảo trì hạ tầng cần được mua sắm bổ sung</h2>

<ul style="padding-left:0;list-style:none">
    <li><strong>Mã cơ sở:</strong> {{ $maintenanceSystem->branch_code }}</li>
    <li><strong>Cơ sở:</strong> {{ $maintenanceSystem->branch_name }}</li>
    <li><strong>Hạng mục:</strong> {{ $maintenanceSystem->issue_name }}</li>
    <li><strong>Sự cố:</strong> {{ $maintenanceSystem->issue_description }}</li>
    <li><strong>Kỹ thuật viên:</strong> {{ $maintenanceSystem->technician_name }}</li>
    <li><strong>Đề xuất khắc phục:</strong> {{ $maintenanceSystem->solution_description }}</li>
</ul>

<p>
    <a href="{{ route('maintenance-system.show', $maintenanceSystem->id) }}">
        Xem chi tiết yêu cầu bảo trì
    </a>
</p>

<p>Yêu cầu cần được mua sắm bổ sung, vui lòng xử lý sớm.</p>