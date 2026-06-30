<div class="p-1">

    {{-- Thông tin chung --}}
    <div class="mb-4">
        <div class="text-xl font-bold mb-1 border-b pb-2">Thông tin chung</div>
        <div class="flex flex-wrap gap-3">
            {{-- Sự cố --}}
            <div class="bg-white rounded shadow-sm p-3 flex-1 min-w-[240px] max-w-[360px]">
                <div class="font-semibold text-base mb-2 border-b pb-1">Sự cố</div>
                <div class="flex flex-col gap-1 text-sm">
                    <div>
                        <span class="font-bold">Mã lỗi: </span>{{ $maintenanceSystem->issue_code }}
                    </div>
                    <div>
                        <span class="font-bold">Tên sự cố: </span>{{ $maintenanceSystem->issue_name }}
                    </div>
                </div>
            </div>
            {{-- Chi nhánh --}}
            <div class="bg-white rounded shadow-sm p-3 flex-1 min-w-[240px] max-w-[360px]">
                <div class="font-semibold text-base mb-2 border-b pb-1">Chi nhánh</div>
                <div class="flex flex-col gap-1 text-sm">
                    <div>
                        <span class="font-bold">Code: </span>{{ $maintenanceSystem->branch_code }}
                    </div>
                    <div>
                        <span class="font-bold">Tên: </span>{{ $maintenanceSystem->branch_name }}
                    </div>
                    <div>
                        <span class="font-bold">Email: </span>{{ $maintenanceSystem->branch_email }}
                    </div>
                </div>
            </div>
            {{-- Kỹ thuật viên --}}
            <div class="bg-white rounded shadow-sm p-3 flex-1 min-w-[240px] max-w-[360px]">
                <div class="font-semibold text-base mb-2 border-b pb-1">Kỹ thuật viên</div>
                <div class="flex flex-col gap-1 text-sm">
                    <div>
                        <span class="font-bold">Họ tên: </span>{{ $maintenanceSystem->technician_name }}
                    </div>
                    <div>
                        <span class="font-bold">Email: </span>{{ $maintenanceSystem->technician_email }}
                    </div>
                    <div>
                        <span class="font-bold">Điện thoại: </span>{{ $maintenanceSystem->technician_phone }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mô tả --}}
    <div class="mb-4">
        <div class="grid grid-cols-2 gap-3">
            <div class="bg-white rounded shadow-sm p-3 flex-1 min-w-[240px] max-w-[500px]">
                <div class="font-semibold text-base mb-2 border-b pb-1">Mô tả</div>
                <div class="flex flex-col gap-1 text-sm">
                    <div>
                        <span class="font-bold">Sự cố: </span>
                        {!! nl2br(e($maintenanceSystem->issue_description ?: '-')) !!}
                    </div>
                    <div>
                        <span class="font-bold">Giải pháp: </span>
                        {!! nl2br(e($maintenanceSystem->solution_description ?: '-')) !!}
                    </div>
                    <div>
                        <span class="font-bold">Lý do trễ: </span>
                        {!! nl2br(e($maintenanceSystem->delay_reason ?: '-')) !!}
                    </div>
                </div>
            </div>
            {{-- Tiến độ --}}
            <div class="bg-white rounded shadow-sm p-3 flex-1 min-w-[240px] max-w-[500px]">
                <div class="font-semibold text-base mb-2 border-b pb-1">Tiến độ xử lý</div>
                <div class="flex flex-col gap-1 text-sm">
                    <div>
                        <span class="font-bold">Ngày yêu cầu: </span>
                        {{ optional($maintenanceSystem->request_at)->format('d/m/Y H:i') }}
                    </div>
                    <div>
                        <span class="font-bold">SLA: </span>
                        {{ $maintenanceSystem->completion_time_code ?: '-' }}
                    </div>
                    <div>
                        <span class="font-bold">Trạng thái: </span>
                        {{ $maintenanceSystem->status ?: '-' }}
                    </div>
                    <div>
                        <span class="font-bold">Hoàn thành: </span>
                        {{ optional($maintenanceSystem->completed_at)->format('d/m/Y H:i') ?? '-' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Nhật ký --}}
    <div class="mb-4">
        <div class="bg-white rounded shadow-sm p-3 flex flex-wrap gap-3">
            <div class="min-w-[160px] flex-1">
                <span class="text-xs text-gray-500">Người tạo</span><br>
                <span class="font-medium">{{ $maintenanceSystem->created_by }}</span>
            </div>
            <div class="min-w-[160px] flex-1">
                <span class="text-xs text-gray-500">Người cập nhật</span><br>
                <span class="font-medium">{{ $maintenanceSystem->updated_by ?: '-' }}</span>
            </div>
            <div class="min-w-[160px] flex-1">
                <span class="text-xs text-gray-500">Người hoàn thành</span><br>
                <span class="font-medium">{{ $maintenanceSystem->completed_by ?: '-' }}</span>
            </div>
            <div class="min-w-[160px] flex-1">
                <span class="text-xs text-gray-500">Tạo lúc</span><br>
                <span class="font-medium">{{ $maintenanceSystem->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="min-w-[160px] flex-1">
                <span class="text-xs text-gray-500">Cập nhật</span><br>
                <span class="font-medium">{{ $maintenanceSystem->updated_at->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <div class="flex justify-end border-t pt-4 mt-2">
        <button
            type="button"
            onclick="closeMaintenanceModal()"
            class="px-4 py-2 rounded border border-gray-300 hover:bg-gray-100">
            Đóng
        </button>
    </div>
</div>