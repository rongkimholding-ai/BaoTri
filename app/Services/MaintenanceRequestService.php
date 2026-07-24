<?php

namespace App\Services;

use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\DB;

class MaintenanceRequestService
{
    public function create(StoreMaintenanceRequest $request): MaintenanceRequest
    {
        return DB::transaction(function () use ($request) {

            $data = $request->validated();

            $data['request_date'] = now();
            $data['sla_status'] = config('sla_status.code.NEW');
            $data['created_by'] = auth()->user()->email;

            // Checkbox
            $data['include_saturday'] = isset($data['include_saturday']) ? (bool) $data['include_saturday'] : false;
            $data['include_sunday'] = isset($data['include_sunday']) ? (bool) $data['include_sunday'] : false;
            $data['include_holiday'] = isset($data['include_holiday']) ? (bool) $data['include_holiday'] : false;

            // Ngoài giờ làm việc
            if (!\App\Helpers\BusinessTimeHelper::isBusinessTime($data['request_date'])) {
                $data['is_off_worktime'] = true;
            }

            $maintenance = MaintenanceRequest::create($data);

            // Upload file đính kèm
            if ($request->hasFile('attachments')) {

                foreach ($request->file('attachments') as $file) {

                    $path = $file->store(
                        'maintenance/attachments',
                        'public'
                    );

                    $maintenance->attachments()->create([
                        'file_name'  => $file->getClientOriginalName(),
                        'file_path'  => $path,
                        'file_type'  => $file->getMimeType(),
                        'file_size'  => $file->getSize(),
                        'created_by' => auth()->user()->email,
                    ]);
                }
            }

            return $maintenance;
        });
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