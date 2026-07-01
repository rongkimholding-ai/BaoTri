<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceSystem extends Model
{
    protected $fillable = [

        'issue_code',
        'issue_name',
        'issue_description',
        'solution_description',

        'branch_code',
        'branch_name',
        'branch_email',

        'technician_name',
        'technician_email',
        'technician_mobile',

        'request_date',
        'actual_completion_date',

        'status',
        'delay_reason',

        'created_at',
        'updated_at',
        'completed_at',

        'created_by',
        'updated_by',
        'completed_by',
    ];

    protected $casts = [

        'request_at' => 'datetime',

        'completed_at' => 'datetime',
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