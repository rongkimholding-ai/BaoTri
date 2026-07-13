<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPendingSystemSlaJob;
use App\Queries\PendingSystemSlaQuery;
use App\Services\LogService;
use Illuminate\Console\Command;

class AutoPendingSystemSlaCommand extends Command
{
    protected $signature = 'sla:auto-system-lated';

    protected $description = 'Auto Hạ tầng chuyển sang Trễ hạn';

    public function handle(PendingSystemSlaQuery $query)
    {
        $time = microtime(true);
        $items = $query->getPendingExpired();

        if ($items->isEmpty()) {
            $this->info('Không có MaintenanceSystem quá SLA.');
            return;
        }

        foreach ($items as $item) {
            logger('Dispatch Pending System SLA Job', [
                'id' => $item->id,
            ]);

            ProcessPendingSystemSlaJob::dispatch($item->id);
        }
        LogService::queue('AUTO SYSTEM LADTED DONE', [
            'request_id' => $item->id,
            'message' => $this->description,
            'duration'   => microtime(true) - $time
        ]);

        $this->info("Queued LATED: {$items->count()} request(s).");

        return;
    }
}