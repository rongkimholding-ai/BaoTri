<?php

namespace App\Observers;

use App\Models\MaintenanceRequest;
use App\Models\TechnicianTarget;

class MaintenanceRequestObserver
{
    public function created(MaintenanceRequest $maintenanceRequest): void
    {
        if (blank($maintenanceRequest->technician_name)) {
            return;
        }

        TechnicianTarget::firstOrCreate(
            [
                'technician_name' => trim(
                    $maintenanceRequest->technician_name
                ),
                'technician_email' => trim(
                    $maintenanceRequest->technician_email
                ),
            ],
            [
                'store_count' => 0,
                'daily_target' => 0,
                'monthly_target' => 0,
            ]
        );
    }
}