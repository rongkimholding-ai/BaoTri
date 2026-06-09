<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequest extends Model
{
    //
    protected $fillable = [
        'branch_code',
        'branch_name',
        'item_category',
        'issue_description',
        'severity',
        'issue_category',
        'technician_name',
        'technician_mobile',
        'standard_completion_time',
        'solution_description',
        'request_date',
        'actual_completion_date',
        'actual_duration',
        'sla_status',
        'delay_reason',
        'outsourced_provider',
        'acceptance_result',
        'acceptance_confirmed_by',
        'pending_at',
        'processing_at',
    ];

    

    public function logs()
    {
        return $this->hasMany(MaintenanceRequestLog::class)
            ->latest();
    }
}
