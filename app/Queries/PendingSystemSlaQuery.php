<?php

namespace App\Queries;

use App\Models\MaintenanceSystem;

class PendingSystemSlaQuery
{
    public function get()
    {
        return MaintenanceSystem::query()
            ->whereIn('status', [
                config('sla_status.code_ht.PENDING'),
                config('sla_status.code_ht.PENDING_CONTRACTOR'),
            ])
            ->whereNotNull('request_date')
            ->whereNotNull('standard_completion_time')
            ->limit(500)
            ->get();
    }
    
    public function getPendingExpired()
    {
        $case = "CASE standard_completion_time ";

        foreach (config('real_time_ht') as $item) {
            $case .= sprintf(
                "WHEN '%s' THEN %d ",
                $item['key'],
                $item['max_seconds']
            );
        }

        $case .= "ELSE NULL END";

        return MaintenanceSystem::query()
            ->whereIn('status', [
                config('sla_status.code_ht.PENDING'),
                config('sla_status.code_ht.PENDING_CONTRACTOR'),
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