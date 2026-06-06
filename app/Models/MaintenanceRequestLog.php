<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceRequestLog extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'user_id',
        'old_status',
        'new_status',
        'note',
    ];

    public function request()
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
