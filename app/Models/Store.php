<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $fillable = [
        'code',
        'name',
        'email',
        'area',
        'region',
        'latitude',
        'longitude',
    
        'am_name',
        'am_email',
    
        'om_name',
        'om_email',
    
        'technician_name',
    
        'muasam_email',
    ];
}
