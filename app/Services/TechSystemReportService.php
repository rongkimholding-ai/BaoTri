<?php

namespace App\Services;

use App\Models\MaintenanceSystem;
use App\Models\TechSystemTarget;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TechSystemReportService
{
    /**
     * Báo cáo tổng hợp theo khoảng request_date,
     * có thể filter thêm theo actual_completion_date.
     */
    public function getReport(
        string $fromDate,
        string $toDate,
        ?string $fromDateCompleted,
        ?string $toDateCompleted,
        array $techEmails = []
    ): Collection {
        $fromDate = Carbon::parse($fromDate)->startOfDay();
        $toDate = Carbon::parse($toDate)->endOfDay();

        $hasFromCompleted = !empty($fromDateCompleted);
        $hasToCompleted = !empty($toDateCompleted);

        if ($hasFromCompleted) {
            $fromDateCompleted = Carbon::parse($fromDateCompleted)->startOfDay();
        }
        if ($hasToCompleted) {
            $toDateCompleted = Carbon::parse($toDateCompleted)->endOfDay();
        }

        $technicians = TechSystemTarget::query()
            ->when(!empty($techEmails), fn($q) => $q->whereIn('technician_email', $techEmails))
            ->get();

        $requestsQuery = MaintenanceSystem::query()
            ->whereBetween('request_date', [$fromDate, $toDate]);

        if ($hasFromCompleted && $hasToCompleted) {
            $requestsQuery->whereBetween('actual_completion_date', [$fromDateCompleted, $toDateCompleted]);
        } elseif ($hasFromCompleted) {
            $requestsQuery->where('actual_completion_date', '>=', $fromDateCompleted);
        } elseif ($hasToCompleted) {
            $requestsQuery->where('actual_completion_date', '<=', $toDateCompleted);
        }

        $requests = $requestsQuery
            ->whereNotNull('technician_email')
            ->when(!empty($techEmails), fn($q) => $q->whereIn('technician_email', $techEmails))
            ->get();

        // group by email đã chuẩn hóa
        $requestsByEmail = $requests->groupBy(fn($item) => strtolower(trim($item->technician_email)));

        return $technicians->map(function ($tech) use ($requestsByEmail) {
            $email = strtolower(trim($tech->technician_email));
            $items = $requestsByEmail->get($email, collect());
            return $this->buildRow($tech, $items);
        });
    }

    protected function buildRow(
        TechSystemTarget $tech,
        Collection $items
    ): object {
        /*
        |--------------------------------------------------------------------------
        | Technician information
        |--------------------------------------------------------------------------
        */

        $code = '';
        $position = '';

        $email = strtolower(trim($tech->technician_email ?? ''));

        $technicianConfig = collect(config('technician_ht', []))
            ->first(
                fn($config) =>
                strtolower(trim($config['email'] ?? '')) === $email
            );

        if ($technicianConfig) {
            $code = $technicianConfig['key'] ?? '';
            $position = $technicianConfig['position'] ?? '';
        }

        $monthlyTarget = (int) ($tech->monthly_target ?? 0);

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $rejectedCode = config('sla_status.code_ht.REJECTED');
        $completedCode = config('sla_status.code_ht.COMPLETED');
        $latedCode = config('sla_status.code_ht.LATED');

        /*
        |--------------------------------------------------------------------------
        | 1. Loại bỏ REJECTED
        |--------------------------------------------------------------------------
        |
        | Tổng công việc = toàn bộ công việc non-REJECTED.
        |
        */

        $filteredItems = $items->filter(
            fn($item) =>
            isset($item->status)
            && $item->status !== $rejectedCode
        );

        /*
        |--------------------------------------------------------------------------
        | 2. Helper
        |--------------------------------------------------------------------------
        */

        $isOnsite = fn($item) =>
            isset($item->work_type)
            && (int) $item->work_type === 1;

        $isOnline = fn($item) =>
            isset($item->work_type)
            && (int) $item->work_type === 2;

        /*
         * false / NULL = trong giờ
         * true         = ngoài giờ
         */
        $isInWorkTime = fn($item) =>
            $item->is_off_worktime === false
            || is_null($item->is_off_worktime);

        $isOffWorkTime = fn($item) =>
            $item->is_off_worktime === true;

        $isCompleted = fn($item) =>
            ($item->status ?? null) === $completedCode;

        $isLate = fn($item) =>
            ($item->status ?? null) === $latedCode;

        $isQualityPass = fn($item) =>
            ($item->acceptance_result ?? null) === 'accepted';

        $isQualityFail = fn($item) =>
            ($item->acceptance_result ?? null) === 'rejected'
            || is_null($item->acceptance_result);

        /*
        |--------------------------------------------------------------------------
        | 3. Tổng số công việc
        |--------------------------------------------------------------------------
        */

        $totalCompleted = $filteredItems->count();

        /*
        |--------------------------------------------------------------------------
        | 4. Phân loại 4 nhóm công việc
        |--------------------------------------------------------------------------
        |
        | Mỗi record hợp lệ phải thuộc đúng 1 trong 4 nhóm:
        |
        | - Onsite trong giờ
        | - Onsite ngoài giờ
        | - Online trong giờ
        | - Online ngoài giờ
        |
        */

        $onsiteInWorkCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isInWorkTime($item)
            )
            ->count();

        $onsiteOffWorkCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isOffWorkTime($item)
            )
            ->count();

        $onlineInWorkCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isInWorkTime($item)
            )
            ->count();

        $onlineOffWorkCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isOffWorkTime($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 5. Record không thể phân loại vào 4 nhóm
        |--------------------------------------------------------------------------
        |
        | Ví dụ:
        | - work_type khác 1 / 2
        | - is_off_worktime có giá trị bất thường
        |
        | Các record này vẫn nằm trong totalCompleted nhưng không thể
        | đưa chính xác vào 1 trong 4 nhóm.
        |
        */

        $classifiedCount =
            $onsiteInWorkCount
            + $onsiteOffWorkCount
            + $onlineInWorkCount
            + $onlineOffWorkCount;

        $unclassifiedCount = max(
            0,
            $totalCompleted - $classifiedCount
        );

        /*
        |--------------------------------------------------------------------------
        | 6. Kiểm tra phân loại
        |--------------------------------------------------------------------------
        */

        if ($classifiedCount + $unclassifiedCount !== $totalCompleted) {
            \Log::warning('KPI classification mismatch', [
                'technician_email' => $email,
                'total_completed' => $totalCompleted,
                'classified_count' => $classifiedCount,
                'unclassified_count' => $unclassifiedCount,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 7. Tỷ lệ hoàn thành / định mức
        |--------------------------------------------------------------------------
        */

        $completionPercent = $this->percent(
            $totalCompleted,
            $monthlyTarget
        );

        /*
        |--------------------------------------------------------------------------
        | 8. Công việc quy đổi
        |--------------------------------------------------------------------------
        |
        | Onsite = 100%
        | Online = 40%
        |
        */

        $quyDoiCount =
            $onsiteInWorkCount
            + $onsiteOffWorkCount
            + (
                ($onlineInWorkCount + $onlineOffWorkCount)
                * 0.4
            );

        $completionQuyDoiPercent = $this->percentFloat(
            $quyDoiCount,
            $monthlyTarget
        );

        /*
        |--------------------------------------------------------------------------
        | 9. Onsite trong giờ - Đúng hạn + CL
        |--------------------------------------------------------------------------
        */

        $onsiteInWorkOnTimeQualityCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isInWorkTime($item)
                && $isCompleted($item)
                && $isQualityPass($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 10. Onsite trong giờ - Trễ
        |--------------------------------------------------------------------------
        */

        $onsiteInWorkLateCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isInWorkTime($item)
                && $isLate($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 11. Onsite trong giờ - Chưa đáp ứng
        |--------------------------------------------------------------------------
        |
        | Là phần còn lại của nhóm sau khi loại:
        |
        | - Đúng hạn + CL
        | - LATED
        |
        | Bao gồm:
        | - COMPLETED nhưng không đạt CL
        | - COMPLETED nhưng acceptance_result NULL
        | - status khác COMPLETED / LATED
        |
        */

        $onsiteInWorkNotMetCount = max(
            0,
            $onsiteInWorkCount
            - $onsiteInWorkOnTimeQualityCount
            - $onsiteInWorkLateCount
        );

        /*
        |--------------------------------------------------------------------------
        | 12. Onsite ngoài giờ - Đúng hạn + CL
        |--------------------------------------------------------------------------
        */

        $onsiteOffWorkOnTimeQualityCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isOffWorkTime($item)
                && $isCompleted($item)
                && $isQualityPass($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 13. Onsite ngoài giờ - Trễ
        |--------------------------------------------------------------------------
        */

        $onsiteOffWorkLateCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isOffWorkTime($item)
                && $isLate($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 14. Onsite ngoài giờ - Chưa đáp ứng
        |--------------------------------------------------------------------------
        */

        $onsiteOffWorkNotMetCount = max(
            0,
            $onsiteOffWorkCount
            - $onsiteOffWorkOnTimeQualityCount
            - $onsiteOffWorkLateCount
        );

        /*
        |--------------------------------------------------------------------------
        | 15. Online trong giờ - Đúng hạn + CL
        |--------------------------------------------------------------------------
        */

        $onlineInWorkOnTimeQualityCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isInWorkTime($item)
                && $isCompleted($item)
                && $isQualityPass($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 16. Online trong giờ - Trễ
        |--------------------------------------------------------------------------
        */

        $onlineInWorkLateCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isInWorkTime($item)
                && $isLate($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 17. Online trong giờ - Chưa đáp ứng
        |--------------------------------------------------------------------------
        */

        $onlineInWorkNotMetCount = max(
            0,
            $onlineInWorkCount
            - $onlineInWorkOnTimeQualityCount
            - $onlineInWorkLateCount
        );

        /*
        |--------------------------------------------------------------------------
        | 18. Online ngoài giờ - Đúng hạn + CL
        |--------------------------------------------------------------------------
        */

        $onlineOffWorkOnTimeQualityCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isOffWorkTime($item)
                && $isCompleted($item)
                && $isQualityPass($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 19. Online ngoài giờ - Trễ
        |--------------------------------------------------------------------------
        */

        $onlineOffWorkLateCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isOffWorkTime($item)
                && $isLate($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 20. Online ngoài giờ - Chưa đáp ứng
        |--------------------------------------------------------------------------
        */

        $onlineOffWorkNotMetCount = max(
            0,
            $onlineOffWorkCount
            - $onlineOffWorkOnTimeQualityCount
            - $onlineOffWorkLateCount
        );

        /*
        |--------------------------------------------------------------------------
        | 21. Kiểm tra 4 nhóm
        |--------------------------------------------------------------------------
        |
        | Mỗi nhóm phải khớp:
        |
        | Tổng nhóm =
        | Đúng hạn + CL
        | + Trễ
        | + Chưa đáp ứng
        |
        */

        $onsiteInWorkCheck =
            $onsiteInWorkOnTimeQualityCount
            + $onsiteInWorkLateCount
            + $onsiteInWorkNotMetCount;

        $onsiteOffWorkCheck =
            $onsiteOffWorkOnTimeQualityCount
            + $onsiteOffWorkLateCount
            + $onsiteOffWorkNotMetCount;

        $onlineInWorkCheck =
            $onlineInWorkOnTimeQualityCount
            + $onlineInWorkLateCount
            + $onlineInWorkNotMetCount;

        $onlineOffWorkCheck =
            $onlineOffWorkOnTimeQualityCount
            + $onlineOffWorkLateCount
            + $onlineOffWorkNotMetCount;

        if (
            $onsiteInWorkCheck !== $onsiteInWorkCount
            || $onsiteOffWorkCheck !== $onsiteOffWorkCount
            || $onlineInWorkCheck !== $onlineInWorkCount
            || $onlineOffWorkCheck !== $onlineOffWorkCount
        ) {
            \Log::warning('KPI detail classification mismatch', [
                'technician_email' => $email,

                'onsite_in_work' => [
                    'total' => $onsiteInWorkCount,
                    'on_time_quality' => $onsiteInWorkOnTimeQualityCount,
                    'late' => $onsiteInWorkLateCount,
                    'not_met' => $onsiteInWorkNotMetCount,
                    'sum' => $onsiteInWorkCheck,
                ],

                'onsite_off_work' => [
                    'total' => $onsiteOffWorkCount,
                    'on_time_quality' => $onsiteOffWorkOnTimeQualityCount,
                    'late' => $onsiteOffWorkLateCount,
                    'not_met' => $onsiteOffWorkNotMetCount,
                    'sum' => $onsiteOffWorkCheck,
                ],

                'online_in_work' => [
                    'total' => $onlineInWorkCount,
                    'on_time_quality' => $onlineInWorkOnTimeQualityCount,
                    'late' => $onlineInWorkLateCount,
                    'not_met' => $onlineInWorkNotMetCount,
                    'sum' => $onlineInWorkCheck,
                ],

                'online_off_work' => [
                    'total' => $onlineOffWorkCount,
                    'on_time_quality' => $onlineOffWorkOnTimeQualityCount,
                    'late' => $onlineOffWorkLateCount,
                    'not_met' => $onlineOffWorkNotMetCount,
                    'sum' => $onlineOffWorkCheck,
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 22. Tổng công việc LATED
        |--------------------------------------------------------------------------
        */

        $totalLateCount =
            $onsiteInWorkLateCount
            + $onsiteOffWorkLateCount
            + $onlineInWorkLateCount
            + $onlineOffWorkLateCount;

        $latePercent = $this->percent(
            $totalLateCount,
            $totalCompleted
        );

        /*
        |--------------------------------------------------------------------------
        | 23. Onsite đạt chất lượng
        |--------------------------------------------------------------------------
        |
        | Bao gồm:
        | - Onsite trong giờ
        | - Onsite ngoài giờ
        |
        */

        $onsiteQualityPassCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isQualityPass($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 24. Onsite không đạt chất lượng
        |--------------------------------------------------------------------------
        */

        $onsiteQualityFailCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isQualityFail($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 25. Online trong giờ đạt chất lượng
        |--------------------------------------------------------------------------
        */

        $onlineInWorkQualityPassCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isInWorkTime($item)
                && $isQualityPass($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 26. Online trong giờ không đạt chất lượng
        |--------------------------------------------------------------------------
        */

        $onlineInWorkQualityFailCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isInWorkTime($item)
                && $isQualityFail($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 27. Online ngoài giờ đạt chất lượng
        |--------------------------------------------------------------------------
        */

        $onlineOffWorkQualityPassCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isOffWorkTime($item)
                && $isQualityPass($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 28. Online ngoài giờ không đạt chất lượng
        |--------------------------------------------------------------------------
        */

        $onlineOffWorkQualityFailCount = $filteredItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isOffWorkTime($item)
                && $isQualityFail($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 29. Tổng công việc không đạt chất lượng
        |--------------------------------------------------------------------------
        */

        $qualityFailCount =
            $onsiteQualityFailCount
            + $onlineInWorkQualityFailCount
            + $onlineOffWorkQualityFailCount;

        $qualityFailPercent = $this->percent(
            $qualityFailCount,
            $totalCompleted
        );

        /*
        |--------------------------------------------------------------------------
        | 30. Return
        |--------------------------------------------------------------------------
        */

        return (object) [
            /*
            |--------------------------------------------------------------------------
            | 1 - 8: Thông tin kỹ thuật viên + tổng
            |--------------------------------------------------------------------------
            */

            'technician_code' => $code,

            'technician_name' => $tech->technician_name
                ?? $technicianConfig['name']
                ?? 'N/A',

            'technician_email' => $tech->technician_email,

            'technician_position' => $position,

            'store_count' => (int) ($tech->store_count ?? 0),

            'daily_target' => (int) ($tech->daily_target ?? 0),

            'monthly_target' => $monthlyTarget,

            'total_completed' => $totalCompleted,

            /*
            |--------------------------------------------------------------------------
            | Record không thể phân loại
            |--------------------------------------------------------------------------
            */

            'unclassified_count' => $unclassifiedCount,

            /*
            |--------------------------------------------------------------------------
            | 9 - 12: Phân loại 4 nhóm công việc
            |--------------------------------------------------------------------------
            */

            'onsite_in_work_count' => $onsiteInWorkCount,

            'onsite_off_work_count' => $onsiteOffWorkCount,

            'online_in_work_count' => $onlineInWorkCount,

            'online_off_work_count' => $onlineOffWorkCount,

            /*
            |--------------------------------------------------------------------------
            | 13 - 15: Tỷ lệ + quy đổi
            |--------------------------------------------------------------------------
            */

            'completion_percent' => $completionPercent,

            'quy_doi_count' => $quyDoiCount,

            'completion_quy_doi_percent' => $completionQuyDoiPercent,

            /*
            |--------------------------------------------------------------------------
            | 16 - 27:
            |
            | Mỗi nhóm:
            | - Đúng hạn + CL
            | - Trễ
            | - Chưa đáp ứng
            |--------------------------------------------------------------------------
            */

            // Onsite trong giờ
            'onsite_in_work_on_time_quality_count' =>
                $onsiteInWorkOnTimeQualityCount,

            'onsite_in_work_late_count' =>
                $onsiteInWorkLateCount,

            'onsite_in_work_not_met_count' =>
                $onsiteInWorkNotMetCount,

            // Onsite ngoài giờ
            'onsite_off_work_on_time_quality_count' =>
                $onsiteOffWorkOnTimeQualityCount,

            'onsite_off_work_late_count' =>
                $onsiteOffWorkLateCount,

            'onsite_off_work_not_met_count' =>
                $onsiteOffWorkNotMetCount,

            // Online trong giờ
            'online_in_work_on_time_quality_count' =>
                $onlineInWorkOnTimeQualityCount,

            'online_in_work_late_count' =>
                $onlineInWorkLateCount,

            'online_in_work_not_met_count' =>
                $onlineInWorkNotMetCount,

            // Online ngoài giờ
            'online_off_work_on_time_quality_count' =>
                $onlineOffWorkOnTimeQualityCount,

            'online_off_work_late_count' =>
                $onlineOffWorkLateCount,

            'online_off_work_not_met_count' =>
                $onlineOffWorkNotMetCount,

            /*
            |--------------------------------------------------------------------------
            | 28 - 29: Tổng LATED
            |--------------------------------------------------------------------------
            */

            'total_late_count' => $totalLateCount,

            'late_percent' => $latePercent,

            /*
            |--------------------------------------------------------------------------
            | 30 - 35: Chất lượng
            |--------------------------------------------------------------------------
            */

            'onsite_quality_pass_count' =>
                $onsiteQualityPassCount,

            'onsite_quality_fail_count' =>
                $onsiteQualityFailCount,

            'online_in_work_quality_pass_count' =>
                $onlineInWorkQualityPassCount,

            'online_in_work_quality_fail_count' =>
                $onlineInWorkQualityFailCount,

            'online_off_work_quality_pass_count' =>
                $onlineOffWorkQualityPassCount,

            'online_off_work_quality_fail_count' =>
                $onlineOffWorkQualityFailCount,

            /*
            |--------------------------------------------------------------------------
            | 36 - 37: Tổng chất lượng
            |--------------------------------------------------------------------------
            */

            'quality_fail_count' => $qualityFailCount,

            'quality_fail_percent' => $qualityFailPercent,
        ];
    }

    /**
     * Báo cáo KPI kỹ thuật viên
     * 19 chỉ tiêu (phiên bản tối ưu hơn)
     */
    public function getKpiReport(
        string $fromDate,
        string $toDate,
        array $techEmails = []
    ): Collection {
        $fromDate = Carbon::parse($fromDate)->startOfDay();
        $toDate = Carbon::parse($toDate)->endOfDay();

        $technicians = TechSystemTarget::query()
            ->when(!empty($techEmails), fn($q) => $q->whereIn('technician_email', $techEmails))
            ->get();

        $items = MaintenanceSystem::query()
            ->whereBetween('request_date', [$fromDate, $toDate])
            ->whereNotNull('technician_email')
            ->when(!empty($techEmails), fn($q) => $q->whereIn('technician_email', $techEmails))
            ->get();

        $itemsByTechnician = $items->groupBy(fn($item) => strtolower(trim($item->technician_email ?? '')));
        return $technicians->map(function ($technician) use ($itemsByTechnician) {
            $email = strtolower(trim($technician->technician_email ?? ''));
            $technicianItems = $itemsByTechnician->get($email, collect());
            return $this->buildKpiRow($technician, $technicianItems);
        })->values();
    }

    protected function buildKpiRow(
        TechSystemTarget $technician,
        Collection $items
    ): object {
        $email = strtolower(
            trim($technician->technician_email ?? '')
        );

        /*
        |--------------------------------------------------------------------------
        | Technician config
        |--------------------------------------------------------------------------
        */

        $conf = collect(config('technician_ht', []))
            ->first(
                fn($t) =>
                strtolower(trim($t['email'] ?? '')) === $email
            );

        $technicianCode = $conf['key'] ?? '';
        $technicianPosition = $conf['position'] ?? '';

        $storeCount = (int) ($technician->store_count ?? 0);
        $dailyTarget = (float) ($technician->daily_target ?? 0);
        $monthlyTarget = (float) ($technician->monthly_target ?? 0);

        /*
        |--------------------------------------------------------------------------
        | SLA status
        |--------------------------------------------------------------------------
        */

        $rejectedCode = config('sla_status.code_ht.REJECTED');
        $completedCode = config('sla_status.code_ht.COMPLETED');
        $latedCode = config('sla_status.code_ht.LATED');

        /*
        |--------------------------------------------------------------------------
        | Chỉ lấy các công việc không bị REJECTED
        |
        | Cột 7 = toàn bộ công việc thực hiện trong tháng
        | sau khi loại REJECTED.
        |--------------------------------------------------------------------------
        */

        $eligibleItems = $items->filter(
            fn($item) =>
            isset($item->status)
            && $item->status !== $rejectedCode
        );

        /*
        |--------------------------------------------------------------------------
        | Helper
        |--------------------------------------------------------------------------
        */

        // work_type:
        // 1 = Onsite
        // 2 = Online

        $isOnsite = fn($item) =>
            (int) ($item->work_type ?? 0) === 1;

        $isOnline = fn($item) =>
            (int) ($item->work_type ?? 0) === 2;

        // false / null = trong giờ hành chính
        // true = ngoài giờ hành chính

        $isInWorkTime = fn($item) =>
            $item->is_off_worktime === false
            || is_null($item->is_off_worktime);

        $isOffWorkTime = fn($item) =>
            $item->is_off_worktime === true;

        $isCompleted = fn($item) =>
            ($item->status ?? null) === $completedCode;

        $isLate = fn($item) =>
            ($item->status ?? null) === $latedCode;

        $isQualityPass = fn($item) =>
            ($item->acceptance_result ?? null) === 'accepted';

        /*
        |--------------------------------------------------------------------------
        | 8-11. Phân loại tổng số công việc
        |--------------------------------------------------------------------------
        */

        // 8. Onsite trong giờ hành chính
        $onsiteInWorkCount = $eligibleItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isInWorkTime($item)
            )
            ->count();

        // 9. Onsite ngoài giờ hành chính
        $onsiteOffWorkCount = $eligibleItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isOffWorkTime($item)
            )
            ->count();

        // 10. Online trong giờ hành chính
        $onlineInWorkCount = $eligibleItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isInWorkTime($item)
            )
            ->count();

        // 11. Online ngoài giờ hành chính
        $onlineOffWorkCount = $eligibleItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isOffWorkTime($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 7. Tổng số vụ sửa chữa thực hiện trong tháng
        |
        | = Onsite trong giờ
        | + Onsite ngoài giờ
        | + Online trong giờ
        | + Online ngoài giờ
        |--------------------------------------------------------------------------
        */

        $totalCompleted =
            $onsiteInWorkCount
            + $onsiteOffWorkCount
            + $onlineInWorkCount
            + $onlineOffWorkCount;

        /*
        |--------------------------------------------------------------------------
        | 12. Tỷ lệ hoàn thành / định mức
        |
        | = Cột 7 / Cột 6
        |--------------------------------------------------------------------------
        */

        $completionPercent = $this->percent(
            $totalCompleted,
            $monthlyTarget
        );

        /*
        |--------------------------------------------------------------------------
        | 13-17. Phân loại KPI theo thời gian + chất lượng
        |--------------------------------------------------------------------------
        */

        // 13.
        // Onsite đạt thời gian + đạt chất lượng
        //
        // Bao gồm:
        // - Onsite trong giờ
        // - Onsite ngoài giờ
        // miễn là COMPLETED + accepted.

        $onsiteOnTimeQualityCount = $eligibleItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isCompleted($item)
                && $isQualityPass($item)
            )
            ->count();

        // 14.
        // Onsite chậm thời gian + đạt chất lượng
        //
        // Bao gồm cả onsite trong giờ và ngoài giờ.

        $onsiteLateQualityCount = $eligibleItems
            ->filter(
                fn($item) =>
                $isOnsite($item)
                && $isLate($item)
                && $isQualityPass($item)
            )
            ->count();

        // 15.
        // Online trong giờ + hoàn thành + đạt chất lượng

        $onlineInWorkQualityCount = $eligibleItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isInWorkTime($item)
                && $isCompleted($item)
                && $isQualityPass($item)
            )
            ->count();

        // 16.
        // Online ngoài giờ + hoàn thành + đạt chất lượng

        $onlineOffWorkQualityCount = $eligibleItems
            ->filter(
                fn($item) =>
                $isOnline($item)
                && $isOffWorkTime($item)
                && $isCompleted($item)
                && $isQualityPass($item)
            )
            ->count();

        /*
        |--------------------------------------------------------------------------
        | 17. Không đạt thời gian, chất lượng
        |
        | Lấy toàn bộ công việc không REJECTED
        | trừ các nhóm KPI đã đạt ở cột 13-16.
        |--------------------------------------------------------------------------
        */

        $classifiedCount =
            $onsiteOnTimeQualityCount
            + $onsiteLateQualityCount
            + $onlineInWorkQualityCount
            + $onlineOffWorkQualityCount;

        $notMetCount = max(
            0,
            $totalCompleted - $classifiedCount
        );

        /*
        |--------------------------------------------------------------------------
        | 18. Số công việc quy đổi
        |
        | = Onsite đạt thời gian + chất lượng
        | + (Onsite chậm + Online trong giờ + Online ngoài giờ) * 40%
        |--------------------------------------------------------------------------
        */

        $quyDoiCount =
            $onsiteOnTimeQualityCount
            + (
                (
                    $onsiteLateQualityCount
                    + $onlineInWorkQualityCount
                    + $onlineOffWorkQualityCount
                ) * 0.4
            );

        /*
        |--------------------------------------------------------------------------
        | Tỷ lệ hoàn thành / định mức quy đổi
        |
        | Theo nhóm công việc:
        |
        | = (
        |     Onsite trong giờ
        |     + Onsite ngoài giờ
        |     + (Online trong giờ + Online ngoài giờ) * 40%
        |   ) / định mức tháng
        |--------------------------------------------------------------------------
        */

        $quyDoiCountByWorkType =
            $onsiteInWorkCount
            + $onsiteOffWorkCount
            + (
                (
                    $onlineInWorkCount
                    + $onlineOffWorkCount
                ) * 0.4
            );

        $completionQuyDoiPercent = $this->percent(
            $quyDoiCountByWorkType,
            $monthlyTarget
        );

        /*
        |--------------------------------------------------------------------------
        | 19. Tỷ lệ hoàn thành KPI
        |
        | = Số công việc quy đổi / định mức tháng
        |--------------------------------------------------------------------------
        */

        $kpiPercent = $this->percent(
            $quyDoiCount,
            $monthlyTarget
        );

        /*
        |--------------------------------------------------------------------------
        | Kiểm tra tổng số công việc
        |
        | Cột 7 phải bằng tổng 4 nhóm:
        |
        | Onsite trong giờ
        | + Onsite ngoài giờ
        | + Online trong giờ
        | + Online ngoài giờ
        |--------------------------------------------------------------------------
        */

        $totalByWorkType =
            $onsiteInWorkCount
            + $onsiteOffWorkCount
            + $onlineInWorkCount
            + $onlineOffWorkCount;

        if ($totalByWorkType !== $totalCompleted) {
            \Log::warning(
                'KPI work type count mismatch',
                [
                    'technician_email' => $email,

                    'total_completed' => $totalCompleted,

                    'onsite_in_work' => $onsiteInWorkCount,
                    'onsite_off_work' => $onsiteOffWorkCount,

                    'online_in_work' => $onlineInWorkCount,
                    'online_off_work' => $onlineOffWorkCount,

                    'total_by_work_type' => $totalByWorkType,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Return
        |--------------------------------------------------------------------------
        */

        return (object) [
            // 1-7
            'technician_code' => $technicianCode,

            'technician_name' =>
                $technician->technician_name
                ?? $conf['name']
                ?? 'N/A',

            'technician_email' => $email,

            'technician_position' => $technicianPosition,

            'store_count' => $storeCount,

            'daily_target' => $dailyTarget,

            'monthly_target' => $monthlyTarget,

            'total_completed' => $totalCompleted,

            // 8-11
            'onsite_in_work_count' => $onsiteInWorkCount,

            'onsite_off_work_count' => $onsiteOffWorkCount,

            'online_in_work_count' => $onlineInWorkCount,

            'online_off_work_count' => $onlineOffWorkCount,

            // 12-13
            'completion_percent' => $completionPercent,

            'completion_quy_doi_percent' => $completionQuyDoiPercent,

            // 14-18
            'onsite_on_time_quality_count' =>
                $onsiteOnTimeQualityCount,

            'onsite_late_quality_count' =>
                $onsiteLateQualityCount,

            'online_in_work_quality_count' =>
                $onlineInWorkQualityCount,

            'online_off_work_quality_count' =>
                $onlineOffWorkQualityCount,

            'not_met_count' =>
                $notMetCount,

            // 19-20
            'quy_doi_count' => $quyDoiCount,

            'kpi_percent' => $kpiPercent,

            /*
            |--------------------------------------------------------------------------
            | Auxiliary
            |--------------------------------------------------------------------------
            */

            'quy_doi_count_by_work_type' =>
                $quyDoiCountByWorkType,
        ];
    }

    /**
     * Tính phần trăm với integer.
     */
    protected function percent(int|float $value, int|float $total): float
    {
        if ($total <= 0)
            return 0;
        return round(($value * 100) / $total, 2);
    }

    /**
     * Tính phần trăm số thực (có thể bỏ nếu hàm percent đã xử lý được cả float/int).
     */
    protected function percentFloat(float $value, int $total): float
    {
        if ($total <= 0)
            return 0;
        return round(($value * 100) / $total, 2);
    }
}