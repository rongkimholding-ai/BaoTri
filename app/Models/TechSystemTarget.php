<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TechSystemTarget extends Model
{
    protected $fillable = [
        'technician_name',
        'technician_email',
        'store_count',
        'daily_target',
        'monthly_target',
    ];
}
