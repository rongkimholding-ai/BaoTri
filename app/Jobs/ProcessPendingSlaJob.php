<?php

namespace App\Jobs;

use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessPendingSlaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $maintenanceRequestId
    ) {
    }

    public function handle(): void
    {

        DB::transaction(function () {

            $item = MaintenanceRequest::whereKey($this->maintenanceRequestId)
                ->lockForUpdate()
                ->first();

            if (!$item) {
                return;
            }

            if (
                !in_array($item->sla_status, [
                    config('sla_status.code.PENDING'),
                    config('sla_status.code.PENDING_CONTRACTOR'),
                ])
            ) {
                return;
            }

            // update + log
            $oldStatus = $item->sla_status;

            $item->update([
                'sla_status' => config('sla_status.code.LATED'),
            ]);

            MaintenanceRequestLog::create([
                'maintenance_request_id' => $item->id,
                'user_id' => 1,
                'old_status' => $oldStatus,
                'new_status' => config('sla_status.code.LATED'),
                'note' => 'Tự động chuyển sang Trễ hạn do quá thời gian xử lý.',
            ]);
        });
    }
}