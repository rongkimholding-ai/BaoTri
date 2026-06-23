<?php

namespace App\Queries;

use App\Models\MaintenanceRequest;

class SlaDueQuery
{
    public function get()
    {
        return MaintenanceRequest::query()
            ->where('sla_status', config('sla_status.code.COMPLETED'))
            ->where(function ($q) {
                $q->where('is_confirmed', 0)
                  ->orWhereNull('is_confirmed');
            })
            ->whereNotNull('actual_completion_date')
            ->whereRaw('actual_completion_date <= DATE_SUB(NOW(), INTERVAL 3 DAY)')
            // ->whereRaw('actual_completion_date <= DATE_SUB(NOW(), INTERVAL 2 MINUTE)')
            ->limit(500)
            ->get();
    }
}