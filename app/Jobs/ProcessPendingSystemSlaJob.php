<?php

namespace App\Jobs;

use App\Models\MaintenanceSystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ProcessPendingSystemSlaJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $maintenanceRequestId
    ) {
    }

    public function handle(): void
    {

        DB::transaction(function () {

            $item = MaintenanceSystem::whereKey($this->maintenanceRequestId)
                ->lockForUpdate()
                ->first();

            logger('check record',[
                'item' => $item,
            ]);

            if (!$item) {
                return;
            }

            if (
                !in_array($item->status, [
                    config('sla_status.code_ht.PENDING'),
                    config('sla_status.code_ht.PENDING_CONTRACTOR'),
                ])
            ) {
                return;
            }

            $newStatus = config('sla_status.code_ht.LATED');

            $result = $item->update([
                'status' => $newStatus,
            ]);

            logger('check after job system lated',[
                'update_result' => $result,
                'after_status' => $item->fresh()->status,
            ]);

            $item->writeLog(
                id: $item->id,
                action: 'AUTO LATED',
                newStatus: $newStatus,
                note: 'Tự động chuyển sang Trễ hạn do quá thời gian xử lý'
            );
        });
    }
}