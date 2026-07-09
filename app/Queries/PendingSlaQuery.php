<?php

namespace App\Queries;

use App\Models\MaintenanceRequest;

class PendingSlaQuery
{
    public function get()
    {
        return MaintenanceRequest::query()
            ->whereIn('sla_status', [
                config('sla_status.code.PENDING'),
                config('sla_status.code.PENDING_CONTRACTOR'),
            ])
            ->whereNotNull('request_date')
            ->whereNotNull('standard_completion_time')
            ->limit(500)
            ->get();
    }
    
    public function getPendingExpired()
    {
        $case = "CASE standard_completion_time ";

        foreach (config('real_time') as $item) {
            $case .= sprintf(
                "WHEN '%s' THEN %d ",
                $item['key'],
                $item['max_seconds']
            );
        }

        $case .= "ELSE NULL END";

        return MaintenanceRequest::query()
            ->whereIn('sla_status', [
                config('sla_status.code.PENDING'),
                config('sla_status.code.PENDING_CONTRACTOR'),
            ])
            ->whereNotNull('request_date')
            ->whereRaw("
                DATE_ADD(
                    request_date,
                    INTERVAL ($case) SECOND
                ) <= NOW()
            ")
            ->limit(500)
            ->get();
    }
}