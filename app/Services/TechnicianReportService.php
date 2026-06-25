<?php

namespace App\Services;

use App\Models\MaintenanceRequest;
use App\Models\TechnicianTarget;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TechnicianReportService
{
    public function getReport(
        string $fromDate,
        string $toDate,
        string $fromDateCompleted,
        string $toDateCompleted,
        array $techEmails = []
    ): Collection {

        $fromDate = Carbon::parse($fromDate)->startOfDay();
        $toDate = Carbon::parse($toDate)->endOfDay();
        $fromDateCompleted = Carbon::parse($fromDateCompleted)->startOfDay();
        $toDateCompleted = Carbon::parse($toDateCompleted)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Technicians
        |--------------------------------------------------------------------------
        */

        $technicians = TechnicianTarget::query()
            ->when(
                !empty($techEmails),
                fn ($q) => $q->whereIn(
                    'technician_email',
                    $techEmails
                )
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Requests
        |--------------------------------------------------------------------------
        */

        $requests = MaintenanceRequest::query()
            ->whereBetween(
                'request_date',
                [$fromDate, $toDate]
            )
            ->whereBetween(
                'actual_completion_date',
                [$fromDateCompleted, $toDateCompleted]
            )
            ->whereNotNull('technician_email')
            ->where(
                'technician_email',
                '!=',
                'liemhoang.support.hcm@tocotocotea.com'
            )
            ->when(
                !empty($techEmails),
                fn ($q) => $q->whereIn(
                    'technician_email',
                    $techEmails
                )
            )
            ->get();

        $requestsByEmail = $requests->groupBy(function ($item) {
            return strtolower(
                trim($item->technician_email)
            );
        });

        return $technicians->map(function ($tech) use ($requestsByEmail) {

            $email = strtolower(
                trim($tech->technician_email)
            );

            $items = $requestsByEmail->get(
                $email,
                collect()
            );

            return $this->buildRow(
                $tech,
                $items
            );
        });
    }

    public function getKpiReport(
        string $fromDate,
        string $toDate,
        array $techEmails = []
    ): Collection {

        $fromDate = Carbon::parse($fromDate)->startOfDay();
        $toDate = Carbon::parse($toDate)->endOfDay();

        /*
        |--------------------------------------------------------------------------
        | Technicians
        |--------------------------------------------------------------------------
        */

        $technicians = TechnicianTarget::query()
            ->when(
                !empty($techEmails),
                fn ($q) => $q->whereIn(
                    'technician_email',
                    $techEmails
                )
            )
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Requests
        |--------------------------------------------------------------------------
        */

        $requests = MaintenanceRequest::query()
            ->whereBetween(
                'request_date',
                [$fromDate, $toDate]
            )
            ->whereNotNull('technician_email')
            ->where(
                'technician_email',
                '!=',
                'liemhoang.support.hcm@tocotocotea.com'
            )
            ->when(
                !empty($techEmails),
                fn ($q) => $q->whereIn(
                    'technician_email',
                    $techEmails
                )
            )
            ->get();

        $requestsByEmail = $requests->groupBy(function ($item) {
            return strtolower(
                trim($item->technician_email)
            );
        });

        return $technicians->map(function ($tech) use ($requestsByEmail) {

            $email = strtolower(
                trim($tech->technician_email)
            );

            $items = $requestsByEmail->get(
                $email,
                collect()
            );

            return $this->buildRow(
                $tech,
                $items
            );
        });
    }

    protected function buildRow(
        TechnicianTarget $tech,
        Collection $items
    ): object {

        // Lấy thông tin position từ config/technician.php dựa theo email
        $position = '';
        $configTechnicians = config('technician');

        // Loại bỏ phần tử 'ngoai_gio' nếu có
        $listTechnicians = collect($configTechnicians)
            // ->filter(function ($item, $key) {
            //     return !(is_string($key) && $key === 'ngoai_gio');
            // })
            ->values();


        // Tìm technician với email khớp
        foreach ($listTechnicians as $item) {
            if (
                isset($item['email']) &&
                strtolower(trim($item['email'])) === strtolower(trim($tech->technician_email))
            ) {
                $position = $item['position'] ?? '';
                break;
            }
        }

        $monthlyTarget = (int) $tech->monthly_target;

        $totalCompleted = $items->count();

        $onTimeCount = $items
            ->where(
                'sla_status',
                config('sla_status.code.COMPLETED')
            )
            ->count();

        $lateCount = $totalCompleted - $onTimeCount;

        $lateAcceptedCount = $items
            ->filter(function ($item) {
                return
                    $item->sla_status === config('sla_status.code.LATED')
                    && $item->acceptance_result === 'accepted';
            })
            ->count();

        $qualityPassCount = $items
            ->where(
                'acceptance_result',
                'accepted'
            )
            ->count();

        $qualityFailCount = $items
            ->filter(function ($item) {
                return
                    $item->acceptance_result === 'rejected'
                    || is_null($item->acceptance_result);
            })
            ->count();

        $offWorkCount = $items
            ->where('is_off_worktime', true)
            ->count();

        return (object) [

            'technician_name' => $tech->technician_name,
            'technician_email' => $tech->technician_email,
            'technician_position' => $position,

            'store_count' => (int) $tech->store_count,
            'daily_target' => (int) $tech->daily_target,
            'monthly_target' => $monthlyTarget,

            'total_completed' => $totalCompleted,

            'ngoai_gio_count' => $offWorkCount,

            'dung_han_count' => $onTimeCount,
            'khong_dung_han_count' => $lateCount,

            'late_accepted_count' => $lateAcceptedCount,

            'quality_pass_count' => $qualityPassCount,
            'quality_fail_count' => $qualityFailCount,

            'completion_percent' =>
                $this->percent(
                    $totalCompleted,
                    $monthlyTarget
                ),

            'dung_han_dm_percent' =>
                $this->percent(
                    $onTimeCount,
                    $monthlyTarget
                ),

            'dung_han_total_percent' =>
                $this->percent(
                    $onTimeCount,
                    $totalCompleted
                ),

            'khong_dung_han_percent' =>
                $this->percent(
                    $lateCount,
                    $totalCompleted
                ),

            'quality_pass_dm_percent' =>
                $this->percent(
                    $qualityPassCount,
                    $monthlyTarget
                ),

            'quality_pass_total_percent' =>
                $this->percent(
                    $qualityPassCount,
                    $totalCompleted
                ),

            'quality_fail_total_percent' =>
                $this->percent(
                    $qualityFailCount,
                    $totalCompleted
                ),
        ];
    }

    protected function percent(
        int $value,
        int $total
    ): float {

        if ($total <= 0) {
            return 0;
        }

        return round(
            ($value * 100) / $total,
            2
        );
    }
}