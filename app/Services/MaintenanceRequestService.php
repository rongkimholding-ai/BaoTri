<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
class MaintenanceRequestService
{
    public function create(array $data)
    {
        return MaintenanceRequest::create($data);
    }

    public function updateField(
        MaintenanceRequest $request,
        string $field,
        mixed $value
    ) {
        $request->$field = $value;

        $request->save();
    }
}