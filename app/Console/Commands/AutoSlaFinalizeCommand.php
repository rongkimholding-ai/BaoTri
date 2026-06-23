<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSlaFinalizationJob;
use App\Queries\SlaDueQuery;
use Illuminate\Console\Command;
use App\Models\MaintenanceRequest;
use App\Services\SlaAutoAcceptanceService;
use Carbon\Carbon;

class AutoSlaFinalizeCommand extends Command
{
    protected $signature = 'sla:auto-finalize';

    public function handle(SlaDueQuery $query)
    {
        $items = $query->get();

        if ($items->isEmpty()) {
            $this->info("Không có Status nào cần Auto");
            return;
        }

        foreach ($items as $item) {
            ProcessSlaFinalizationJob::dispatch($item->id);
        }

        $this->info("Queued: " . $items->count());
    }
}