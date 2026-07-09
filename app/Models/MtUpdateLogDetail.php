<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MtUpdateLogDetail extends Model
{
    protected $table = 'mt_update_log_details';

    protected $fillable = [
        'mt_update_log_id',
        'field',
        'field_name',
        'old_value',
        'new_value',
    ];

    public function log()
    {
        return $this->belongsTo(
            MtUpdateLog::class,
            'mt_update_log_id'
        );
    }
}