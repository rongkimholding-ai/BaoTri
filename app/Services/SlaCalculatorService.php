<?php

namespace App\Services;

use App\Helpers\BusinessTimeHelper;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceSystem;
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

    /**
     * Đánh giá SLA status từ actual_duration và standard_completion_time.
     */
    public function determineSlaStatus(MaintenanceRequest $item): string
    {
        if (!$item->actual_duration) return config('sla_status.code.LATED');

        [$h, $i, $s] = array_pad(explode(':', $item->actual_duration), 3, 0);
        $actualSeconds = ($h * 3600) + ($i * 60) + $s;

        $realTimeList = config('real_time');
        $realTimeMap = collect($realTimeList)->keyBy('key');
        $stdKey = $item->standard_completion_time;
        if (!$stdKey || !isset($realTimeMap[$stdKey])) return config('sla_status.code.LATED');

        $maxSeconds = $realTimeMap[$stdKey]['max_seconds'] ?? 0;

        return $actualSeconds <= $maxSeconds
            ? config('sla_status.code.COMPLETED')
            : config('sla_status.code.LATED');
    }

    public function determineSystemSlaStatus(MaintenanceSystem $item): string
    {
        if (!$item->actual_duration) return config('sla_status.code_ht.LATED');

        [$h, $i, $s] = array_pad(explode(':', $item->actual_duration), 3, 0);
        $actualSeconds = ($h * 3600) + ($i * 60) + $s;

        $realTimeList = config('real_time_ht');
        $realTimeMap = collect($realTimeList)->keyBy('key');
        $stdKey = $item->standard_completion_time;

        // Nếu không lấy được max_seconds (null), giữ nguyên status hiện tại
        if (!$stdKey || !isset($realTimeMap[$stdKey]) || !isset($realTimeMap[$stdKey]['max_seconds']) || is_null($realTimeMap[$stdKey]['max_seconds'])) {
            return $item->status ?? config('sla_status.code_ht.LATED');
        }

        $maxSeconds = $realTimeMap[$stdKey]['max_seconds'];

        return $actualSeconds <= $maxSeconds
            ? config('sla_status.code_ht.COMPLETED')
            : config('sla_status.code_ht.LATED');
    }
}
