<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MtUpdateLog extends Model
{
    protected $table = 'mt_update_logs';

    protected $fillable = [
        'maintenance_request_id',
        'user_id',
        'note',
    ];

    public function maintenanceRequest()
    {
        return $this->belongsTo(
            MaintenanceRequest::class,
            'maintenance_request_id'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function details()
    {
        return $this->hasMany(
            MtUpdateLogDetail::class,
            'mt_update_log_id'
        );
    }
}