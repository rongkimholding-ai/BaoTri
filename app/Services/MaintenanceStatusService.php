<?php

namespace App\Services;

use App\Http\Requests\ChangeMaintenanceStatusRequest;
use App\Jobs\UploadMaintenanceImagesJob;
use App\Mail\MaintenanceBuyerMail;
use App\Mail\MaintenanceCompletedMail;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MaintenanceStatusService
{
    protected array $statusConfig;

    public function __construct(
        protected SlaCalculatorService $slaCalculatorService,
        protected LogService $logService
    ) {
        $this->statusConfig = config('sla_status.code');
    }

    /**
     * Thay đổi trạng thái yêu cầu bảo trì
     */
    public function changeStatus(
        MaintenanceRequest $maintenanceRequest,
        ChangeMaintenanceStatusRequest $request
    ): array {
        $log = fn($msg, $extra = []) => $this->logService::maintenance($msg, ['request_id' => $maintenanceRequest->id] + $extra);

        $log('ENTER changeStatus', ['time' => microtime(true)]);
        $validated = $request->validated();
        $status = $validated['status'];
        $oldStatus = $maintenanceRequest->sla_status;
        $now = now();
        $start = microtime(true);

        $data = $this->buildUpdateData($maintenanceRequest, $request, $status, $now);

        $this->updateMaintenance($maintenanceRequest, $data, $oldStatus, $status, $request, $start);

        $maintenanceRequest->refresh();

        $this->afterStatusChanged($maintenanceRequest, $status, $request);

        $log('AFTER STATUS DONE', ['duration' => microtime(true) - $start]);
        $log('LEAVE changeStatus', ['time' => microtime(true)]);

        return [
            'success' => true,
            'sla_status' => $maintenanceRequest->sla_status,
        ];
    }

    /**
     * Sinh dữ liệu update
     */
    private function buildUpdateData(
        MaintenanceRequest $maintenanceRequest,
        ChangeMaintenanceStatusRequest $request,
        string $status,
        $now
    ): array {
        $user = Auth::user();
        $data = [
            'sla_status'    => $status,
            'delay_reason'  => $request->note,
        ];

        switch ($status) {
            case $this->statusConfig['WAITING_CONFIRM']:
                $data += $this->handleWaitingConfirm($maintenanceRequest, $request, $now, $user);
                break;
            case $this->statusConfig['CONFIRMED']:
                $data['sla_status'] = $this->slaCalculatorService->determineSlaStatus($maintenanceRequest);
                break;
            case $this->statusConfig['PENDING']:
            case $this->statusConfig['PENDING_CONTRACTOR']:
                $data['pending_at'] = $now;
                break;
            case $this->statusConfig['CONTINUE_PROCESSING']:
                $data['processing_at'] = $now;
                break;
            case $this->statusConfig['REOPEN']:
                $data += [
                    'acceptance_result'        => null,
                    'acceptance_note'          => null,
                    'acceptance_confirmed_by'  => null,
                    'confirmed_at'             => null,
                    'is_confirmed'             => false,
                ];
                break;
        }
        return $data;
    }

    /**
     * Xử lý riêng cho WAITING_CONFIRM
     */
    private function handleWaitingConfirm(
        MaintenanceRequest $maintenanceRequest,
        ChangeMaintenanceStatusRequest $request,
        $now,
        $user
    ): array {
        $data = [
            'actual_completion_date' => $now,
            'delay_reason'           => '',
            'actual_duration'        => null,
        ];

        // Tính SLA
        try {
            $data['actual_duration'] = $this->slaCalculatorService
                ->calculate($maintenanceRequest, $now);
        } catch (\Throwable $e) {
            LogService::error('maintenance', 'SLA duration calculation failed', [
                'request_id' => $maintenanceRequest->id,
                'exception' => $e->getMessage(),
            ]);
            $data['actual_duration'] = '00:00:00';
        }

        // Xử lý chọn kỹ thuật viên ngoài giờ
        if ($user?->email === 'baotri@tocotocotea.com') {
            $data['is_off_worktime'] = true;
            $selectedTechEmail = $request->input('tech_mail');
            if ($selectedTechEmail) {
                $tech = collect(config('technician', []))
                    ->first(fn($tech) => ($tech['email'] ?? null) === $selectedTechEmail);
                if ($tech) {
                    $data['technician_email']  = $tech['email'];
                    $data['technician_name']   = $tech['name'];
                    $data['technician_mobile'] = $tech['mobile'];
                }
            }
        }

        return $data;
    }

    /**
     * Update DB
     */
    private function updateMaintenance(
        MaintenanceRequest $maintenanceRequest,
        array $data,
        string $oldStatus,
        string $newStatus,
        ChangeMaintenanceStatusRequest $request,
        float $start
    ): void {
        $this->logService->maintenance('UPDATE TRANSACTION START', [
            'request_id' => $maintenanceRequest->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'data' => $data
        ]);

        DB::transaction(function () use ($maintenanceRequest, $data, $oldStatus, $newStatus, $request) {
            $maintenanceRequest->update($data);
            MaintenanceRequestLog::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'user_id'                => auth()->id(),
                'old_status'             => $oldStatus,
                'new_status'             => $newStatus,
                'note'                   => $request->note,
            ]);
        });

        $this->logService->maintenance('UPDATE DONE', [
            'request_id' => $maintenanceRequest->id,
            'duration'   => microtime(true) - $start
        ]);
    }

    /**
     * Sau khi update thành công
     */
    private function afterStatusChanged(
        MaintenanceRequest $maintenanceRequest,
        string $requestedStatus,
        ChangeMaintenanceStatusRequest $request
    ): void {
        if ($requestedStatus === $this->statusConfig['WAITING_CONFIRM']) {
            $this->autoConfirm($maintenanceRequest);
        }
        $this->handleUploadImages($maintenanceRequest, $requestedStatus, $request);
        $this->handleSendMail($maintenanceRequest, $requestedStatus);
    }

    /**
     * Auto xác nhận
     */
    private function autoConfirm(MaintenanceRequest $maintenanceRequest): void
    {
        $maintenanceRequest->refresh();
        $newStatus = $this->slaCalculatorService->determineSlaStatus($maintenanceRequest);
        if ($maintenanceRequest->sla_status === $newStatus) return;

        DB::transaction(function () use ($maintenanceRequest, $newStatus) {
            $maintenanceRequest->update(['sla_status' => $newStatus]);
            MaintenanceRequestLog::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'user_id'                => 1,
                'old_status'             => $this->statusConfig['WAITING_CONFIRM'],
                'new_status'             => $newStatus,
                'note'                   => 'Auto duyệt yêu cầu',
            ]);
        });
    }

    /**
     * Upload ảnh
     */
    private function handleUploadImages(
        MaintenanceRequest $maintenanceRequest,
        string $requestedStatus,
        ChangeMaintenanceStatusRequest $request
    ): void {
        $time = microtime(true);
        if (
            $requestedStatus !== $this->statusConfig['WAITING_CONFIRM'] ||
            ! $request->hasFile('images')
        ) return;

        $files = $request->file('images');
        LogService::queue('Danh sách file upload', [
            'request_id' => $maintenanceRequest->id,
            'files' => collect($files)->map(fn($file) => [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ])
        ]);

        $tempFiles = [];
        foreach ($files as $file) {
            $tempName = Str::uuid() . '.' . $file->extension();
            $file->storeAs('temp-maintenance', $tempName);
            $tempFiles[] = $tempName;
        }

        UploadMaintenanceImagesJob::dispatch(
            $maintenanceRequest->id,
            $tempFiles,
            $request->input('technician_mail') ?: (auth()->user()?->email)
        );

        LogService::queue('UPLOAD DONE', [
            'request_id' => $maintenanceRequest->id,
            'duration'   => microtime(true) - $time
        ]);
    }

    /**
     * Gửi mail
     */
    private function handleSendMail(
        MaintenanceRequest $maintenanceRequest,
        string $requestedStatus
    ): void {
        $time = microtime(true);

        try {
            switch ($requestedStatus) {
                case $this->statusConfig['WAITING_CONFIRM']:
                    Mail::to($maintenanceRequest->branch_email)
                        ->queue(new MaintenanceCompletedMail($maintenanceRequest));
                    break;
                case $this->statusConfig['PENDING']:
                    Mail::to($maintenanceRequest->technician_email)
                        ->queue(new MaintenanceBuyerMail($maintenanceRequest));
                    break;
            }
        } catch (\Throwable $e) {
            LogService::error('mail', 'Send mail failed', [
                'request_id' => $maintenanceRequest->id,
                'status'     => $requestedStatus,
                'exception'  => $e->getMessage(),
            ]);
        }
        LogService::mail('MAIL DONE', [
            'request_id' => $maintenanceRequest->id,
            'duration'   => microtime(true) - $time,
        ]);
    }

    
}