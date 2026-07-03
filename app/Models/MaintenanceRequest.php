<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    //
    protected $fillable = [
        'branch_code',
        'branch_name',
        'branch_email',
        'item_category',
        'issue_description',
        'severity',
        // 'issue_category',
        'technician_name',
        'technician_mobile',
        'technician_email',
        'standard_completion_time',
        'include_saturday',
        'include_sunday',
        'include_holiday',
        'is_off_worktime',
        'solution_description',
        'request_date',
        'actual_completion_date',
        'actual_duration',
        'sla_status',
        'delay_reason',
        'outsourced_provider',
        'is_confirmed',
        'acceptance_result',
        'acceptance_note',
        'acceptance_confirmed_by',
        'pending_at',
        'processing_at',
        'confirmed_at',
        'created_by',
    ];

    

    public function logs()
    {
        return $this->hasMany(MaintenanceRequestLog::class)
        ->orderByDesc('created_at')
        ->orderByDesc('id');
    }

    public function images()
    {
        return $this->hasMany(MaintenanceRequestImage::class);
    }
}
