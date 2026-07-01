<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceSystem extends Model
{
    protected $fillable = [
        // Thông tin sự cố / Dịch vụ
        'issue_code',
        'issue_name',
        'issue_description',
        'solution_description',

        // Chi nhánh (Snapshot từ stores.json)
        'branch_code',
        'branch_name',
        'branch_email',

        // Kỹ thuật viên xử lý (Snapshot)
        'technician_name',
        'technician_email',
        'technician_mobile',

        // SLA và thời gian xử lý
        'request_date',
        'actual_completion_date',
        'completed_at',
        'standard_completion_time',
        'include_saturday',
        'include_sunday',
        'include_holiday',
        'is_off_worktime',
        'actual_duration',
        'sla_status',

        // Trạng thái, lý do trễ, nhà thầu ngoài
        'status',
        'delay_reason',

        // Nghiệm thu
        'acceptance_result',
        'acceptance_note',
        'acceptance_confirmed_by',
        'is_confirmed',
        'confirmed_at',

        // Người thao tác (Email)
        'created_by',
        'updated_by',
        'completed_by',

        // Reminder / tự động
        'pending_at',
        'processing_at',
    ];

    protected $casts = [
        // DateTime fields
        'request_date' => 'datetime',
        'completed_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'pending_at' => 'datetime',
        'processing_at' => 'datetime',
        // Boolean fields
        'include_saturday' => 'boolean',
        'include_sunday' => 'boolean',
        'include_holiday' => 'boolean',
        'is_off_worktime' => 'boolean',
        'is_confirmed' => 'boolean',
    ];

    public function logs()
    {
        return $this->hasMany(MaintenanceSystemLog::class)
            ->latest();
    }

    public function writeLog(
        string $action,
        ?string $oldStatus = null,
        ?string $newStatus = null,
        ?string $note = null
    ): void {
        $this->logs()->create([
            'action' => $action,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'note' => $note,
            'performed_by' => auth()->user()->email,
        ]);
    }
}