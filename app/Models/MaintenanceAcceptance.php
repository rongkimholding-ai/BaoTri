<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenanceAcceptance extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'result',
        'note',
        'confirmed_by',
        'confirmed_at',
    ];

    public function request()
    {
        return $this->belongsTo(
            MaintenanceRequest::class,
            'maintenance_request_id'
        );
    }
}
