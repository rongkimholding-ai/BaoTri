<?php

namespace App\Services;

use App\Models\MaintenanceSystem;

class SlaEngineSystemService
{
    public function evaluate(MaintenanceSystem $item): string
    {
        return $item->status;
    }
}