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
        // Đảm bảo các trường boolean được set đúng giá trị theo input (checkbox unchecked sẽ không có key)
        $data['include_saturday'] = isset($data['include_saturday']) ? (bool)$data['include_saturday'] : false;
        $data['include_sunday'] = isset($data['include_sunday']) ? (bool)$data['include_sunday'] : false;
        $data['include_holiday'] = isset($data['include_holiday']) ? (bool)$data['include_holiday'] : false;

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