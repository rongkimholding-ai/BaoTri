@php
    $statusName = config('sla_status.names.' . $maintenanceSystem->status) ?? $maintenanceSystem->status;
    $statusBadge = config('sla_status.badge.' . $maintenanceSystem->status) ?? 'bg-secondary';

    $slaName = $maintenanceSystem->completion_time_code
        ? str_replace('_', ' ', $maintenanceSystem->completion_time_code)
        : '-';
@endphp

<div>

    <ul class="nav nav-tabs mb-3" id="maintenanceDetailTab" role="tablist">

        <li class="nav-item">

            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-information" type="button">

                Thông tin chung

            </button>

        </li>

        <li class="nav-item">

            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history" type="button">

                Lịch sử xử lý

                <span class="badge bg-secondary">

                    {{ $maintenanceSystem->logs->count() }}

                </span>

            </button>

        </li>

    </ul>

    <div class="tab-content">

        <div class="tab-pane fade show active" id="tab-information">

            <div class="row">

                <div class="col-md-6 mb-4">

                    <div class="card shadow-sm h-100">

                        <div class="card-header bg-light fw-bold">

                            Thông tin sự cố

                        </div>

                        <div class="card-body">

                            <table class="table table-borderless table-sm mb-0">

                                <tr>

                                    <th width="180">

                                        Mã lỗi

                                    </th>

                                    <td>

                                        {{ $maintenanceSystem->issue_code }}

                                    </td>

                                </tr>

                                <tr>

                                    <th>

                                        Tên sự cố

                                    </th>

                                    <td>

                                        {{ $maintenanceSystem->issue_name }}

                                    </td>

                                </tr>

                                <tr>

                                    <th>

                                        SLA

                                    </th>

                                    <td>

                                        {{ $slaName }}

                                    </td>

                                </tr>

                                <tr>

                                    <th>

                                        Trạng thái

                                    </th>

                                    <td>

                                        <span class="badge {{ $statusBadge }}">

                                            {{ $statusName }}

                                        </span>

                                    </td>

                                </tr>

                            </table>

                        </div>

                    </div>

                </div>

                <div class="col-md-6 mb-4">

                    <div class="card shadow-sm h-100">

                        <div class="card-header bg-light fw-bold">

                            Chi nhánh & Kỹ thuật viên

                        </div>

                        <div class="card-body">

                            <table class="table table-borderless table-sm mb-0">

                                <tr>

                                    <th width="180">

                                        Mã chi nhánh

                                    </th>

                                    <td>

                                        {{ $maintenanceSystem->branch_code ?: '-' }}

                                    </td>

                                </tr>

                                <tr>

                                    <th>

                                        Chi nhánh

                                    </th>

                                    <td>

                                        {{ $maintenanceSystem->branch_name ?: '-' }}

                                    </td>

                                </tr>

                                <tr>

                                    <th>

                                        Email chi nhánh

                                    </th>

                                    <td>

                                        {{ $maintenanceSystem->branch_email ?: '-' }}

                                    </td>

                                </tr>

                                <tr>

                                    <th>

                                        Kỹ thuật viên

                                    </th>

                                    <td>

                                        {{ $maintenanceSystem->technician_name ?: '-' }}

                                    </td>

                                </tr>

                                <tr>

                                    <th>

                                        Email KTV

                                    </th>

                                    <td>

                                        {{ $maintenanceSystem->technician_email ?: '-' }}

                                    </td>

                                </tr>

                                <tr>

                                    <th>

                                        Điện thoại

                                    </th>

                                    <td>

                                        {{ $maintenanceSystem->technician_mobile ?: '-' }}

                                    </td>

                                </tr>

                            </table>

                        </div>

                    </div>

                </div>

                <div class="col-md-12 mb-4">

                    <div class="card shadow-sm">

                        <div class="card-header bg-light fw-bold">

                            Nội dung

                        </div>

                        <div class="card-body">

                            <div class="mb-4">

                                <strong>

                                    Mô tả sự cố

                                </strong>

                                <div class="border rounded p-3 mt-2 bg-light">

                                    {!! nl2br(e($maintenanceSystem->issue_description ?: '-')) !!}

                                </div>

                            </div>

                            <div class="mb-4">

                                <strong>

                                    Mô tả khắc phục

                                </strong>

                                <div class="border rounded p-3 mt-2 bg-light">

                                    {!! nl2br(e($maintenanceSystem->solution_description ?: '-')) !!}

                                </div>

                            </div>

                            <div>

                                <strong>

                                    Lý do trễ

                                </strong>

                                <div class="border rounded p-3 mt-2 bg-light">

                                    {!! nl2br(e($maintenanceSystem->delay_reason ?: '-')) !!}

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-md-12">

                    <div class="card shadow-sm">

                        <div class="card-header bg-light fw-bold">

                            Thời gian xử lý

                        </div>

                        <div class="card-body">

                            <table class="table table-bordered table-sm">

                                <tbody>

                                    <tr>

                                        <th width="220">

                                            Ngày yêu cầu

                                        </th>

                                        <td>

                                            {{ optional($maintenanceSystem->request_at)->format('d/m/Y H:i') }}

                                        </td>

                                    </tr>

                                    <tr>

                                        <th>

                                            Tạo lúc

                                        </th>

                                        <td>

                                            {{ optional($maintenanceSystem->created_at)->format('d/m/Y H:i:s') }}

                                        </td>

                                    </tr>

                                    <tr>

                                        <th>

                                            Cập nhật

                                        </th>

                                        <td>

                                            {{ optional($maintenanceSystem->updated_at)->format('d/m/Y H:i:s') }}

                                        </td>

                                    </tr>

                                    <tr>

                                        <th>

                                            Hoàn thành

                                        </th>

                                        <td>

                                            {{ optional($maintenanceSystem->completed_at)->format('d/m/Y H:i:s') ?: '-' }}

                                        </td>

                                    </tr>

                                    <tr>

                                        <th>

                                            Người tạo

                                        </th>

                                        <td>

                                            {{ $maintenanceSystem->created_by ?: '-' }}

                                        </td>

                                    </tr>

                                    <tr>

                                        <th>

                                            Người cập nhật

                                        </th>

                                        <td>

                                            {{ $maintenanceSystem->updated_by ?: '-' }}

                                        </td>

                                    </tr>

                                    <tr>

                                        <th>

                                            Người hoàn thành

                                        </th>

                                        <td>

                                            {{ $maintenanceSystem->completed_by ?: '-' }}

                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>
        <div class="tab-pane fade" id="tab-history">

            <div class="card shadow-sm">

                <div class="card-header bg-light d-flex justify-content-between align-items-center">

                    <span class="fw-bold">

                        Lịch sử xử lý

                    </span>

                    <span class="badge bg-secondary">

                        {{ $maintenanceSystem->logs->count() }} bản ghi

                    </span>

                </div>

                <div class="card-body p-0">

                    <div class="table-responsive">

                        <table class="table table-hover table-bordered align-middle mb-0">

                            <thead class="table-light">

                                <tr>

                                    <th width="170">
                                        Thời gian
                                    </th>

                                    <th width="170">
                                        Người thực hiện
                                    </th>

                                    <th width="170">
                                        Thao tác
                                    </th>

                                    <th width="220">
                                        Trạng thái
                                    </th>

                                    <th>
                                        Ghi chú
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                @forelse($maintenanceSystem->logs as $log)

                                    @php

                                        $actionColor = match ($log->action) {

                                            'CREATE' => 'success',

                                            'UPDATE' => 'primary',

                                            'CHANGE_STATUS' => 'warning',

                                            'CHANGE_TECHNICIAN' => 'info',

                                            'CHANGE_SLA' => 'secondary',

                                            'COMPLETE' => 'success',

                                            'REOPEN' => 'danger',

                                            default => 'dark'

                                        };

                                        $actionName = config('maintenance_log.actions')[$log->action]
                                            ?? $log->action;

                                        $oldStatus = config('sla_status.names')[$log->old_status]
                                            ?? $log->old_status;

                                        $newStatus = config('sla_status.names')[$log->new_status]
                                            ?? $log->new_status;

                                    @endphp

                                    <tr>

                                        <td>

                                            {{ $log->created_at->format('d/m/Y H:i:s') }}

                                        </td>

                                        <td>

                                            {{ $log->performed_by }}

                                        </td>

                                        <td>

                                            <span class="badge bg-{{ $actionColor }}">

                                                {{ $actionName }}

                                            </span>

                                        </td>

                                        <td>

                                            @if($log->old_status)

                                                <span class="badge bg-secondary">

                                                    {{ $oldStatus }}

                                                </span>

                                                <i class="bi bi-arrow-right mx-1"></i>

                                            @endif

                                            @if($log->new_status)

                                                <span class="badge bg-primary">

                                                    {{ $newStatus }}

                                                </span>

                                            @else

                                                -

                                            @endif

                                        </td>

                                        <td>

                                            {{ $log->note ?: '-' }}

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td colspan="5" class="text-center text-muted py-4">

                                            Chưa có lịch sử xử lý.

                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>