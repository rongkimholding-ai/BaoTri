<?php

namespace App\Services;

use App\Models\MaintenanceSystem;
use App\Models\TechnicianTarget;
use App\Models\TechSystemTarget;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TechSystemReportService
{
    public function getReport(
        string $fromDate,
        string $toDate,
        ?string $fromDateCompleted,
        ?string $toDateCompleted,
        array $techEmails = []
    ): Collection {

        $fromDate = Carbon::parse($fromDate)->startOfDay();
        $toDate = Carbon::parse($toDate)->endOfDay();

        // Xử lý trường hợp fromDateCompleted và toDateCompleted có thể null
        $hasFromCompleted = !empty($fromDateCompleted);
        $hasToCompleted = !empty($toDateCompleted);

        if ($hasFromCompleted) {
            $fromDateCompleted = Carbon::parse($fromDateCompleted)->startOfDay();
        }
        if ($hasToCompleted) {
            $toDateCompleted = Carbon::parse($toDateCompleted)->endOfDay();
        }

        /*
        |--------------------------------------------------------------------------
        | Technicians
        |--------------------------------------------------------------------------
        */

        $technicians = TechSystemTarget::query()
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

        $requestsQuery = MaintenanceSystem::query()
            ->whereBetween(
                'request_date',
                [$fromDate, $toDate]
            );

        // Chỉ filter by actual_completion_date khi có truyền from/to
        if ($hasFromCompleted && $hasToCompleted) {
            $requestsQuery->whereBetween(
                'actual_completion_date',
                [$fromDateCompleted, $toDateCompleted]
            );
        } elseif ($hasFromCompleted) {
            $requestsQuery->where(
                'actual_completion_date',
                '>=',
                $fromDateCompleted
            );
        } elseif ($hasToCompleted) {
            $requestsQuery->where(
                'actual_completion_date',
                '<=',
                $toDateCompleted
            );
        }

        $requests = $requestsQuery
            ->whereNotNull('technician_email')
            // ->where(
            //     'technician_email',
            //     '!=',
            //     'liemhoang.support.hcm@tocotocotea.com'
            // )
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

        $technicians = TechSystemTarget::query()
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

        $requests = MaintenanceSystem::query()
            ->whereBetween(
                'request_date',
                [$fromDate, $toDate]
            )
            ->whereNotNull('technician_email')
            // ->where(
            //     'technician_email',
            //     '!=',
            //     'liemhoang.support.hcm@tocotocotea.com'
            // )
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
        TechSystemTarget $tech,
        Collection $items
    ): object {

        // Lấy thông tin position từ config/technician.php dựa theo email
        $position = '';
        $configTechnicians = config('technician_ht');

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
                $code = $item['key'] ?? '';
                break;
            }
        }

        $monthlyTarget = (int) $tech->monthly_target;

        // Bỏ qua các trạng thái REJECTED
        $filteredItems = $items->filter(function ($item) {
            return isset($item->status) && $item->status !== config('sla_status.code_ht.REJECTED');
        });

        $totalCompleted = $filteredItems->count();

        $onTimeCount = $filteredItems
            ->where('status', config('sla_status.code_ht.COMPLETED'))
            ->filter(function ($item) {
                // Chỉ tính những yêu cầu trong giờ hành chính (is_off_worktime = false hoặc null)
                return ($item->is_off_worktime === false || is_null($item->is_off_worktime))
                    && $item->acceptance_result === 'accepted';
            })
            ->count();

        $lateAcceptedCount = $filteredItems
            ->filter(function ($item) {
                return
                    $item->status === config('sla_status.code_ht.LATED')
                    && $item->acceptance_result === 'accepted';
            })
            ->count();

        $onTimeOffWorkCount = $filteredItems
            ->where('status', config('sla_status.code_ht.COMPLETED'))
            ->filter(function ($item) {
                // Chỉ tính những yêu cầu ngoài giờ hành chính (is_off_worktime = true)
                return $item->is_off_worktime === true && $item->acceptance_result === 'accepted';
            })
            ->count();

        $qualityPassCount = $filteredItems
            ->where(
                'acceptance_result',
                'accepted'
            )
            ->count();

        $qualityFailCount = $filteredItems
            ->filter(function ($item) {
                return
                    $item->acceptance_result === 'rejected'
                    || is_null($item->acceptance_result);
            })
            ->count();
            
        $lateCount = $totalCompleted - $onTimeCount;

        $offWorkCount = $filteredItems
            ->where('is_off_worktime', true)
            ->count();

        $inWorkCount = $totalCompleted - $offWorkCount;

        $quyDoi = ($onTimeCount 
        + ($lateAcceptedCount * 50 / 100) 
        + ($onTimeOffWorkCount * 30 / 100));

        return (object) [

            'technician_code' => $code,
            'technician_name' => $tech->technician_name,
            'technician_email' => $tech->technician_email,
            'technician_position' => $position,

            'store_count' => (int) $tech->store_count,
            'daily_target' => (int) $tech->daily_target,
            'monthly_target' => $monthlyTarget,

            'total_completed' => $totalCompleted,

            'ngoai_gio_count' => $offWorkCount,
            'trong_gio_count' => $inWorkCount,

            'dung_han_count' => $onTimeCount,
            'khong_dung_han_count' => $lateCount,
            'dung_han_ngoai_gio_count' => $onTimeOffWorkCount,
            'quy_doi_count' => $quyDoi,

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

            'dung_han_quydoi_percent' => ($monthlyTarget > 0) ? round(
                $quyDoi / $monthlyTarget * 100,
                2
            ) : 0,

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