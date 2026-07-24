<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequestAttachment extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'created_by',
    ];

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }
}