<?php

namespace App\Services;

use App\Helpers\BusinessTimeHelper;
use Carbon\Carbon;

class SlaCalculatorService
{
    /**
     * Calculate business time duration between request start and completion.
     *
     * @param mixed $model Should have request_date, include_saturday, include_sunday, include_holiday. Optionally severity.
     * @param Carbon $completedAt
     * @return string
     */
    public function calculate(
        $model,
        Carbon $completedAt
    ): string {
        $seconds = BusinessTimeHelper::diffInBusinessSeconds(
            $model->request_date,
            $completedAt,
            $model->include_saturday,
            $model->include_sunday,
            $model->include_holiday,
            // If model has "severity" property and it's not null, pass it
            (property_exists($model, 'severity') && !is_null($model->severity)) ? $model->severity : null
        );

        return BusinessTimeHelper::formatDuration($seconds);
    }
}
