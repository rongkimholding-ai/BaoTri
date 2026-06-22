<?php

namespace App\Services;

use App\Helpers\BusinessTimeHelper;
use App\Models\MaintenanceRequest;
use Carbon\Carbon;

class SlaCalculatorService
{
    public function calculate(
        MaintenanceRequest $request,
        Carbon $completedAt
    ): string {
        $seconds = BusinessTimeHelper::diffInBusinessSeconds(
            $request->request_date,
            $completedAt,
            $request->include_saturday,
            $request->include_sunday,
            $request->include_holiday,
            $request->severity,
        );

        return BusinessTimeHelper::formatDuration(
            $seconds
        );
    }
}
