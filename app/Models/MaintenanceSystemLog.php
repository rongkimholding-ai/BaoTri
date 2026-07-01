<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceSystemLog extends Model
{
    protected $fillable = [
        'maintenance_system_id',

        'action',

        'old_status',
        'new_status',

        'note',

        'performed_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function maintenanceSystem(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSystem::class);
    }
}