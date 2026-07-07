<?php

namespace App\Jobs;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestLog;
use App\Services\SlaEngineService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessSlaFinalizationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $maintenanceRequestId
    ) {
    }

    public function handle(
        SlaEngineService $service
    ): void {

        $item = MaintenanceRequest::find(
            $this->maintenanceRequestId
        );

        if (! $item) {
            return;
        }

        $oldStatus = $item->sla_status;

        // $status = $service->evaluate($item);

        // \Log::info('AUTO SLA', [
        //     'id' => $item->id,
        // ]);
        \App\Services\LogService::maintenance("AUTO SLA", [
            'time' => microtime(true),
            'request_id' => $item->id,
        ]);

        $item->update([
            // 'sla_status' => $status,
            'is_confirmed' => true,
            'acceptance_result' => 'accepted',
            'acceptance_note' => 'AUTO nghiệm thu từ hệ thống',
            'acceptance_confirmed_by' => 'SYSTEM',
            'confirmed_at' => now(),
        ]);

        MaintenanceRequestLog::create([
            'maintenance_request_id' => $item->id,
            'user_id'                => 1,
            'old_status'             => $oldStatus,
            'new_status'             => $oldStatus,
            'note'                   => 'Tự động nghiệm thu sau 3 ngày không phản hồi',
        ]);
    }
}