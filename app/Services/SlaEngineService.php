<?php

namespace App\Services;

use App\Models\MaintenanceRequest;

class SlaEngineService
{
    public function evaluate(MaintenanceRequest $item): string
    {
        $actualSeconds = strtotime($item->actual_completion_date)
            - strtotime($item->request_date);

        $map = collect(config('real_time'))->keyBy('key');
        $std = $map[$item->standard_completion_time] ?? null;

        if (!$std) {
            return config('sla_status.code.LATED');
        }

        return $actualSeconds <= $std['max_seconds']
            ? config('sla_status.code.COMPLETED')
            : config('sla_status.code.LATED');
    }
}