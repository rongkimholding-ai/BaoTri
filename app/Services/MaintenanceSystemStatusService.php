<?php

namespace App\Services;

use App\Http\Requests\ChangeMaintenanceSystemStatusRequest;
use App\Jobs\UploadSystemImagesJob;
use App\Mail\MaintenanceBuyerMail;
use App\Mail\MaintenanceCompletedMail;
use App\Mail\MaintenanceSystemBuyerMail;
use App\Mail\MaintenanceSystemCompletedMail;
use App\Models\maintenanceSystem;
use App\Models\maintenanceSystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class MaintenanceSystemStatusService
{
    protected array $statusConfig;

    public function __construct(
        protected SlaCalculatorService $slaCalculatorService,
        protected LogService $logService
    ) {
        $this->statusConfig = config('sla_status.code_ht');
    }

    /**
     * Thay đổi trạng thái yêu cầu bảo trì
     */
    public function changeStatus(
        MaintenanceSystem $maintenanceSystem,
        ChangeMaintenanceSystemStatusRequest $request
    ): array {
        try {
            $log = fn($msg, $extra = []) => $this->logService::maintenance($msg, ['request_id' => $maintenanceSystem->id] + $extra);

            $log('ENTER changeStatus', ['time' => microtime(true)]);
            $validated = $request->validated();
            $status = $validated['status'];
            $oldStatus = $maintenanceSystem->status;
            $now = now();
            $start = microtime(true);

            $data = $this->buildUpdateData($maintenanceSystem, $request, $status, $now);

            $this->updateMaintenance($maintenanceSystem, $data, $oldStatus, $status, $request, $start);

            $maintenanceSystem->refresh();

            $this->afterStatusChanged($maintenanceSystem, $status, $request);

            $log('AFTER STATUS DONE', ['duration' => microtime(true) - $start]);
            $log('LEAVE changeStatus', ['time' => microtime(true)]);

            return [
                'success' => true,
                'status' => $maintenanceSystem->status,
            ];
        } catch (\Throwable $e) {
            LogService::error(
                'maintenance',
                'CHANGE STATUS EXCEPTION',
                [
                    'request_id' => $maintenanceSystem->id ?? null,
                    'message'    => $e->getMessage(),
                    'file'       => $e->getFile(),
                    'line'       => $e->getLine(),
                    'trace'      => $e->getTraceAsString(),
                ]
            );
            throw $e;
        }
    }

    /**
     * Sinh dữ liệu update
     */
    private function buildUpdateData(
        MaintenanceSystem $maintenanceSystem,
        ChangeMaintenanceSystemStatusRequest $request,
        string $status,
        $now
    ): array {
        $user = Auth::user();
        $data = [
            'status'    => $status,
            'delay_reason'  => $request->note,
        ];

        if ($request->tech_mail) {
            $tech = collect(config('technician_ht', []))
            ->first(fn($tech) => ($tech['email'] ?? null) === $request->tech_mail);
            if ($tech) {
                $data['technician_email']  = $tech['email'];
                $data['technician_name']   = $tech['name'];
                $data['technician_mobile'] = $tech['mobile'];
            }
        }

        switch ($status) {
            case $this->statusConfig['WAITING_CONFIRM']:
                $data += $this->handleWaitingConfirm($maintenanceSystem, $request, $now, $user);
                break;
            case $this->statusConfig['CONFIRMED']:
                $data['status'] = $this->slaCalculatorService->determineSystemSlaStatus($maintenanceSystem);
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
        MaintenanceSystem $maintenanceSystem,
        ChangeMaintenanceSystemStatusRequest $request,
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
                ->calculate($maintenanceSystem, $now);
        } catch (\Throwable $e) {
            LogService::error('maintenance', 'SLA duration calculation failed', [
                'request_id' => $maintenanceSystem->id,
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
        MaintenanceSystem $maintenanceSystem,
        array $data,
        string $oldStatus,
        string $newStatus,
        ChangeMaintenanceSystemStatusRequest $request,
        float $start
    ): void {
        $this->logService->system('UPDATE TRANSACTION START', [
            'request_id' => $maintenanceSystem->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'data' => $data
        ]);

        DB::transaction(function () use ($maintenanceSystem, $data, $oldStatus, $newStatus, $request) {
            $maintenanceSystem->update($data);
            // maintenanceSystemLog::create([
            //     'maintenance_system_id'  => $maintenanceSystem->id,
            //     'user_id'                => auth()->id(),
            //     'old_status'             => $oldStatus,
            //     'new_status'             => $newStatus,
            //     'note'                   => $request->note,
            // ]);
            $maintenanceSystem->writeLog(
                id: $maintenanceSystem->id,
                action: 'CHANGE_STATUS',
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                note: $request->note,
            );
        });

        $this->logService->system('UPDATE DONE', [
            'request_id' => $maintenanceSystem->id,
            'duration'   => microtime(true) - $start
        ]);
    }

    /**
     * Sau khi update thành công
     */
    private function afterStatusChanged(
        MaintenanceSystem $maintenanceSystem,
        string $requestedStatus,
        ChangeMaintenanceSystemStatusRequest $request
    ): void {
    
        if ($requestedStatus === $this->statusConfig['WAITING_CONFIRM']) {
            $this->autoConfirm($maintenanceSystem);
        }
        $this->handleUploadImages($maintenanceSystem, $requestedStatus, $request);
        $this->handleSendMail($maintenanceSystem, $requestedStatus);
    }

    /**
     * Auto xác nhận
     */
    private function autoConfirm(MaintenanceSystem $maintenanceSystem): void
    {
        $maintenanceSystem->refresh();
        $slaService = app(SlaCalculatorService::class);
        $oldStatus = $maintenanceSystem->status;
        $newStatus = $slaService->determineSystemSlaStatus($maintenanceSystem);
        if ($maintenanceSystem->status === $newStatus) return;

        DB::transaction(function () use ($maintenanceSystem, $newStatus, $oldStatus) {
            $maintenanceSystem->update(['status' => $newStatus]);

            $maintenanceSystem->writeLog(
                id: $maintenanceSystem->id,
                action: 'CHANGE_STATUS',
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                note: 'Auto duyệt yêu cầu'
            );
        });
    }

    /**
     * Upload ảnh
     */
    private function handleUploadImages(
        MaintenanceSystem $maintenanceSystem,
        string $requestedStatus,
        ChangeMaintenanceSystemStatusRequest $request
    ): void {
        $time = microtime(true);
        if (
            $requestedStatus !== $this->statusConfig['WAITING_CONFIRM'] ||
            ! $request->hasFile('images')
        ) return;

        $files = $request->file('images');
        LogService::queue('Danh sách file upload', [
            'request_id' => $maintenanceSystem->id,
            'files' => collect($files)->map(fn($file) => [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ])
        ]);

        $tempFiles = [];
        foreach ($files as $file) {
            $tempName = Str::uuid() . '.' . $file->extension();
            $file->storeAs('temp-system', $tempName);
            $tempFiles[] = $tempName;
        }

        UploadSystemImagesJob::dispatch(
            $maintenanceSystem->id,
            $tempFiles,
            $request->input('technician_mail') ?: (auth()->user()?->email)
        );

        LogService::queue('UPLOAD DONE', [
            'request_id' => $maintenanceSystem->id,
            'duration'   => microtime(true) - $time
        ]);
    }

    /**
     * Gửi mail
     */
    private function handleSendMail(
        maintenanceSystem $maintenanceSystem,
        string $requestedStatus
    ): void {
        $time = microtime(true);

        try {
            switch ($requestedStatus) {
                case $this->statusConfig['WAITING_CONFIRM']:
                    Mail::to($maintenanceSystem->branch_email)
                        ->queue(new MaintenanceSystemCompletedMail($maintenanceSystem));
                    break;
                case $this->statusConfig['PENDING']:
                    Mail::to($maintenanceSystem->technician_email)
                        ->queue(new MaintenanceSystemBuyerMail($maintenanceSystem));
                    break;
            }
        } catch (\Throwable $e) {
            LogService::error('mail', 'Send mail failed', [
                'request_id' => $maintenanceSystem->id,
                'status'     => $requestedStatus,
                'exception'  => $e->getMessage(),
            ]);
        }
        LogService::mail('MAIL DONE', [
            'request_id' => $maintenanceSystem->id,
            'duration'   => microtime(true) - $time,
        ]);
    }

    
}