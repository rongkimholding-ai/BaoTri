<?php

namespace App\Console\Commands;

use App\Jobs\ProcessSlaFinalizationJob;
use App\Jobs\ProcessSlaFinalizationSystemJob;
use App\Queries\SlaDueQuery;
use Illuminate\Console\Command;

class AutoSlaFinalizeCommand extends Command
{
    protected $signature = 'sla:auto-finalize';

    public function handle(SlaDueQuery $query)
    {
        $items = $query->get();

        if ($items->isEmpty()) {
            $this->info("Không có Maintenances nào cần Auto");
        } else {
            foreach ($items as $item) {
                logger('Dispatch Maintenances Job', ['id' => $item->id]);
                ProcessSlaFinalizationJob::dispatch($item->id);
            }

            $this->info("Queued Maintenances: " . $items->count());
        }

        $systemItems = $query->getSystem();

        if ($systemItems->isEmpty()) {
            $this->info("Không có System nào cần Auto");
        } else {
            foreach ($systemItems as $item) {
                logger('Dispatch System Job', ['id' => $item->id]);

                ProcessSlaFinalizationSystemJob::dispatch($item->id);
            }

            $this->info("Queued System: " . $systemItems->count());
        }

    }
}