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
            
            $requests = TechnicianTarget::query()
            ->leftJoin(
                'maintenance_requests',
                function ($join) use ($startDate, $endDate) {
                    $join->on(
                        'technician_targets.technician_name',
                        '=',
                        'maintenance_requests.technician_name'
                    )
                    ->whereBetween(
                        'maintenance_requests.request_date',
                        [$startDate, $endDate]
                    );
                }
            )
            ->selectRaw("
                technician_targets.technician_name,
        
                COUNT(maintenance_requests.id) as total,
        
                SUM(
                    CASE
                        WHEN maintenance_requests.sla_status = 'COMPLETED'
                        THEN 1
                        ELSE 0
                    END
                ) as dung_han_count,
        
                SUM(
                    CASE
                        WHEN maintenance_requests.sla_status <> 'COMPLETED'
                        OR maintenance_requests.sla_status IS NULL
                        THEN 1
                        ELSE 0
                    END
                ) as con_lai_count,
        
                ROUND(
                    COALESCE(
                        SUM(
                            CASE
                                WHEN maintenance_requests.sla_status = 'COMPLETED'
                                THEN 1
                                ELSE 0
                            END
                        ) * 100 / NULLIF(COUNT(maintenance_requests.id), 0),
                        0
                    ),
                    2
                ) as dung_han_percent,
        
                ROUND(
                    COALESCE(
                        SUM(
                            CASE
                                WHEN maintenance_requests.sla_status <> 'COMPLETED'
                                OR maintenance_requests.sla_status IS NULL
                                THEN 1
                                ELSE 0
                            END
                        ) * 100 / NULLIF(COUNT(maintenance_requests.id), 0),
                        0
                    ),
                    2
                ) as con_lai_percent,
        
                technician_targets.store_count,
                technician_targets.daily_target,
                technician_targets.monthly_target,
        
                GREATEST(
                    COUNT(maintenance_requests.id) - COALESCE(technician_targets.monthly_target, 0),
                    0
                ) as vuot_dinh_muc
            ")
            ->groupBy(
                'technician_targets.technician_name',
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