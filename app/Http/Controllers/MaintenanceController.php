<?php

namespace App\Http\Controllers;

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
        $exportName = rand(1, 2000) . date('Ymd') . '_report.xlsx';
        return Excel::download(
            new MaintenanceRequestsExport(),
            $exportName
        );
    }

    public function exportTechs()
    {
        $month = request(
            'month',
            now()->format('Y-m')
        );
        $exportName = rand(1, 2000) . date('Ymd') . '_tech_report.xlsx';
        return Excel::download(
            new TechnicianReportExport($month),
            $exportName
        );
    }

    public function index()
    {
        $month = request(
            'month',
            now()->format('Y-m')
        );
    
        $startDate = Carbon::parse($month . '-01')
            ->startOfMonth();
    
        $endDate = Carbon::parse($month . '-01')
            ->endOfMonth();
            
        $requests = MaintenanceRequest::query()
            ->leftJoin(
                'technician_targets',
                'maintenance_requests.technician_name',
                '=',
                'technician_targets.technician_name'
            )
            ->selectRaw("
                maintenance_requests.technician_name,

                COUNT(*) as total,

                SUM(
                    CASE
                        WHEN sla_status = 'Đúng hạn'
                        THEN 1
                        ELSE 0
                    END
                ) as dung_han_count,

                SUM(
                    CASE
                        WHEN sla_status <> 'Đúng hạn'
                        OR sla_status IS NULL
                        THEN 1
                        ELSE 0
                    END
                ) as con_lai_count,

                ROUND(
                    SUM(
                        CASE
                            WHEN sla_status = 'Đúng hạn'
                            THEN 1
                            ELSE 0
                        END
                    ) * 100 / COUNT(*),
                    2
                ) as dung_han_percent,

                ROUND(
                    SUM(
                        CASE
                            WHEN sla_status <> 'Đúng hạn'
                            OR sla_status IS NULL
                            THEN 1
                            ELSE 0
                        END
                    ) * 100 / COUNT(*),
                    2
                ) as con_lai_percent,

                technician_targets.store_count,
                technician_targets.daily_target,
                technician_targets.monthly_target,

                GREATEST(
                    COUNT(*) - COALESCE(technician_targets.monthly_target, 0),
                    0
                ) as vuot_dinh_muc
            ")
            ->whereBetween(
                'maintenance_requests.request_date',
                [$startDate, $endDate]
            )
            ->whereNotNull('maintenance_requests.technician_name')
            ->where('maintenance_requests.technician_name', '<>', '')
            ->groupBy(
                'maintenance_requests.technician_name',
                'technician_targets.store_count',
                'technician_targets.daily_target',
                'technician_targets.monthly_target'
            )
            ->get();

        return view('reports.technicians', compact('requests'));
    }

    public function updateTarget(Request $request)
    {
        TechnicianTarget::updateOrCreate(
            [
                'technician_name' => $request->technician_name,
            ],
            [
                'store_count' => $request->store_count,
                'daily_target' => $request->daily_target,
                'monthly_target' => $request->monthly_target,
            ]
        );

        return response()->json([
            'success' => true,
        ]);
    }
}