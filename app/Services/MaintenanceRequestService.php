<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
class MaintenanceRequestService
{
    public function create(array $data)
    {
        $data['request_date'] = now();
        $data['sla_status'] = config('sla_status.code.NEW');
        $data['created_by'] = auth()->user()->email;
        // dd($data);

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