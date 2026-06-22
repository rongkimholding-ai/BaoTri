<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestLog;
use Carbon\Carbon;

class SlaAutoAcceptanceService
{
    public function handle(MaintenanceRequest $item): void
    {
        // chặn chạy lại
        if ($item->is_confirmed) {
            return;
        }

        if (!$item->actual_completion_date || !$item->request_date) {
            return;
        }

        // tính thời gian thực tế
        $actualSeconds = Carbon::parse($item->request_date)
            ->diffInSeconds(Carbon::parse($item->actual_completion_date));

        // lấy SLA config
        $realTimeMap = collect(config('real_time'))->keyBy('key');
        $std = $realTimeMap[$item->standard_completion_time] ?? null;

        if (!$std) {
            return;
        }

        // so sánh SLA
        $status = $actualSeconds <= $std['max_seconds']
            ? config('sla_status.code.COMPLETED')
            : config('sla_status.code.LATED');

        // update record
        $item->sla_status = $status;
        $item->is_confirmed = 1;
        $item->acceptance_result = 'accepted';
        $item->acceptance_note = 'auto nghiệm thu hệ thống';
        $item->confirmed_at = now();

        $item->save();

        // log
        MaintenanceRequestLog::create([
            'maintenance_request_id' => $item->id,
            'user_id' => 1,
            'old_status' => 'CONFIRMED',
            'new_status' => $status,
            'note' => 'auto nghiệm thu hệ thống',
        ]);
    }
}