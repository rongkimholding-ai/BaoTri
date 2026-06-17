<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequestImage extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'path',
        'uploaded_by',
    ];

    public function maintenanceRequest()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }
}
