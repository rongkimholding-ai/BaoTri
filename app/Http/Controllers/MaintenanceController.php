<?php

namespace App\Http\Controllers;

use App\Exports\MaintenanceByBranchExport;
use App\Exports\TechnicianReportExport;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestsExport;
use App\Models\TechnicianTarget;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class MaintenanceController extends Controller
{
    public function export()
    {
        $startDate = request(
            'from-date',
            Carbon::now()->startOfMonth()->format('Y-m-d')
        );
        $endDate = request(
            'to-date',
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );
        $exportName = rand(1, 2000) . date('Ymd') . '_report.xlsx';
        return Excel::download(
            new MaintenanceRequestsExport($startDate,$endDate),
            $exportName
        );
    }

    public function exportTechs()
    {
        $startDate = request(
            'from-date',
            Carbon::now()->startOfMonth()->format('Y-m-d')
        );
        $endDate = request(
            'to-date',
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );
        $exportName = rand(1, 2000) . date('Ymd') . '_tech_report.xlsx';
        return Excel::download(
            new TechnicianReportExport($startDate,$endDate),
            $exportName
        );
    }

    public function index()
    {
        $startDate = request(
            'from-date',
            Carbon::now()->startOfMonth()->format('Y-m-d')
        );
        $endDate = request(
            'to-date',
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );

        // Lấy danh sách technicians từ technician_targets (mỗi người 1 record)
        $technicians = TechnicianTarget::all();

        // Lấy maintenance_requests với mọi technician (không group chung tên)
        $requestsRaw = MaintenanceRequest::query()
            ->whereBetween('request_date', [$startDate, $endDate])
            ->get();

        // Group đúng từng technician theo unique key (ưu tiên id hoặc sử dụng tên/email nếu unique)
        // Ở đây sẽ group theo technician_name + (technician_email nếu có để chính xác)
        $keyBy = function ($item) {
            // Nếu có field email, dùng cả tên+email, nếu không chỉ technician_name
            return $item->technician_name . '|' . ($item->technician_email ?? '');
        };

        $requestsByTech = $requestsRaw->groupBy($keyBy);

        // Map theo technician_targets: mỗi record chỉ thống kê cho đúng person
        $requests = $technicians->map(function ($tech) use ($requestsByTech) {
            $key = $tech->technician_name . '|' . ($tech->technician_email ?? '');

            $requests = $requestsByTech->get($key, collect());
            $totalCompleted = $requests->count();

            $monthlyTarget = (int) $tech->monthly_target;

            // Số đúng hạn (sla_status COMPLETED): 
            $dung_han_count = $requests
                ->where('sla_status', config('sla_status.code.COMPLETED'))->count();

            // Số KHÔNG đúng hạn: tất cả bản ghi completion nhưng sla_status != COMPLETED
            $khong_dung_han_count = $requests
                ->where('sla_status', '!=', config('sla_status.code.COMPLETED'))->count();

            $quality_pass_count = $requests->where('acceptance_result', 'accepted')->count();
            $quality_fail_count = $requests->where(function($item) {
                return $item->acceptance_result === 'rejected' || is_null($item->acceptance_result);
            })->count();
       

            $completion_percent =
                $monthlyTarget > 0
                    ? round($totalCompleted * 100 / $monthlyTarget, 2)
                    : 0;

            $dung_han_dm_percent =
                $monthlyTarget > 0
                    ? round($dung_han_count * 100 / $monthlyTarget, 2)
                    : 0;

            $dung_han_total_percent =
                $totalCompleted > 0
                    ? round($dung_han_count * 100 / $totalCompleted, 2)
                    : 0;

            $khong_dung_han_percent =
                $totalCompleted > 0
                    ? round($khong_dung_han_count * 100 / $totalCompleted, 2)
                    : 0;

            $quality_pass_dm_percent =
                $monthlyTarget > 0
                    ? round($quality_pass_count * 100 / $monthlyTarget, 2)
                    : 0;

            $quality_pass_total_percent =
                $totalCompleted > 0
                    ? round($quality_pass_count * 100 / $totalCompleted, 2)
                    : 0;

            $quality_fail_total_percent =
                $totalCompleted > 0
                    ? round($quality_fail_count * 100 / $totalCompleted, 2)
                    : 0;

            return (object)[
                'technician_name'            => $tech->technician_name,
                'technician_email'           => $tech->technician_email ?? '',
                'store_count'                => $tech->store_count,
                'daily_target'               => $tech->daily_target,
                'monthly_target'             => $tech->monthly_target,

                'total_completed'            => $totalCompleted,
                'dung_han_count'             => $dung_han_count,
                'khong_dung_han_count'       => $khong_dung_han_count,
                'quality_pass_count'         => $quality_pass_count,
                'quality_fail_count'         => $quality_fail_count,

                'completion_percent'         => $completion_percent,
                'dung_han_dm_percent'        => $dung_han_dm_percent,
                'dung_han_total_percent'     => $dung_han_total_percent,
                'khong_dung_han_percent'     => $khong_dung_han_percent,
                'quality_pass_dm_percent'    => $quality_pass_dm_percent,
                'quality_pass_total_percent' => $quality_pass_total_percent,
                'quality_fail_total_percent' => $quality_fail_total_percent,
            ];
        });

        return view('reports.technicians', compact('requests'));
    }

    public function updateTarget(Request $request)
    {
        TechnicianTarget::updateOrCreate(
            [
                'technician_name' => $request->technician_name,
            ],
            [
                'store_count'     => $request->store_count,
                'daily_target'    => $request->daily_target,
                'monthly_target'  => $request->monthly_target,
            ]
        );

        return response()->json([
            'success' => true,
        ]);
    }

    public function exportFromTo(Request $request)
    {
        $request->validate([
            'from_date' => ['required', 'date'],
            'to_date'   => ['required', 'date'],
        ]);
        $fromDate = $request->from_date;
        $toDate = $request->to_date;

        $fileName = 'THỐNG KÊ TỪ '.$fromDate.' ĐẾN '.$toDate;

        return Excel::download(
            new MaintenanceByBranchExport(
                $fromDate,
                $toDate
            ),
            $fileName.'.xlsx'
        );
    }
}