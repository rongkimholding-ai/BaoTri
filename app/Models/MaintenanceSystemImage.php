<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceSystemImage extends Model
{
    protected $fillable = [
        'maintenance_system_id',
        'path',
        'uploaded_by',
    ];

    public function maintenanceSystem()
    {
        return $this->belongsTo(MaintenanceSystem::class);
    }
}
