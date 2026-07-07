<?php

namespace App\Jobs;

use App\Models\MaintenanceSystem;
use App\Models\MaintenanceSystemLog;
use App\Services\SlaEngineSystemService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessSlaFinalizationSystemJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $maintenanceRequestId
    ) {
    }

    public function handle(
        SlaEngineSystemService $service
    ): void {

        $item = MaintenanceSystem::find(
            $this->maintenanceRequestId
        );

        if (! $item) {
            return;
        }

        $oldStatus = $item->status;

        // $status = $service->evaluate($item);

        // \Log::info('AUTO SLA SYSTEM', [
        //     'id' => $item->id,
        // ]);
        \App\Services\LogService::system("AUTO SLA SYSTEM", [
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

        $item->writeLog(
            id: $item->id,
            action: 'ACCEPTANCE',
            oldStatus: $oldStatus,
            newStatus: $oldStatus,
            note: 'Tự động nghiệm thu sau 3 ngày không phản hồi',
        );
   
    }
}