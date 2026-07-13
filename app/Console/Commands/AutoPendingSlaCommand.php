<?php

namespace App\Console\Commands;

use App\Jobs\ProcessPendingSlaJob;
use App\Queries\PendingSlaQuery;
use App\Services\LogService;
use Illuminate\Console\Command;

class AutoPendingSlaCommand extends Command
{
    protected $signature = 'sla:auto-lated';

    protected $description = 'Auto Cở sở chuyển sang Trễ hạn';

    public function handle(PendingSlaQuery $query)
    {
        $time = microtime(true);
        $items = $query->getPendingExpired();

        if ($items->isEmpty()) {
            $this->info('Không có MaintenanceRequest quá SLA.');
            return;
        }

        foreach ($items as $item) {
            logger('Dispatch Pending SLA Job', [
                'id' => $item->id,
            ]);

            ProcessPendingSlaJob::dispatch($item->id);
        }
        LogService::queue('AUTO LADTED DONE', [
            'request_id' => $item->id,
            'message' => $this->description,
            'duration'   => microtime(true) - $time
        ]);

        $this->info("Queued Maintenance LATED: {$items->count()} request(s).");

        return;
    }
}