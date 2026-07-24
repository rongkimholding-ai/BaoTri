<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceSystemAttachment extends Model
{
    protected $fillable = [
        'maintenance_system_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'created_by',
    ];

    public function maintenanceSystem()
    {
        return $this->belongsTo(MaintenanceSystem::class);
    }
}
