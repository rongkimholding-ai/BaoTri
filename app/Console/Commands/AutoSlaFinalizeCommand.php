<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MaintenanceRequest;
use App\Services\SlaAutoAcceptanceService;
use Carbon\Carbon;

class AutoSlaFinalizeCommand extends Command
{
    protected $signature = 'sla:auto-finalize';

    protected $description = 'Auto finalize SLA after 3 days CONFIRMED';

    public function handle()
    {
        $items = MaintenanceRequest::query()
            ->where('sla_status', config('sla_status.code.CONFIRMED'))
            ->where('is_confirmed', false)
            ->whereNotNull('actual_completion_date')
            ->where('actual_completion_date', '<=', Carbon::now()->subDays(3))
            ->limit(200) // tránh load quá nặng
            ->get();

        foreach ($items as $item) {
            app(SlaAutoAcceptanceService::class)->handle($item);
        }

        $this->info("Processed: {$items->count()}");
    }
}