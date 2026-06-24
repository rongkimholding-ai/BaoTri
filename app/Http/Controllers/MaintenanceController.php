<?php

namespace App\Http\Controllers;

use App\Exports\MaintenanceByBranchExport;
use App\Exports\TechnicianKpiExport;
use App\Exports\TechnicianReportExport;
use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestsExport;
use App\Models\TechnicianTarget;
use App\Services\TechnicianReportService;
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

        $techEmails = request('tech_emails', []);

        $exportName =
            'maintenance_requests_' .
            now()->format('Ymd_His') .
            '.xlsx';

        return Excel::download(
            new MaintenanceRequestsExport(
                $startDate,
                $endDate,
                $techEmails
            ),
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

        $techEmails = request('tech_emails', []);

        $exportName =
            now()->format('Ymd_His')
            . '_tech_report.xlsx';

        return Excel::download(
            new TechnicianReportExport(
                $startDate,
                $endDate,
                $techEmails
            ),
            $exportName
        );
    }

    public function index(
        TechnicianReportService $service
    )
    {
        $startDate = request(
            'from-date',
            now()->startOfMonth()->format('Y-m-d')
        );
    
        $endDate = request(
            'to-date',
            now()->endOfMonth()->format('Y-m-d')
        );
    
        $techEmails = request(
            'tech_emails',
            []
        );
    
        $requests = $service->getReport(
            $startDate,
            $endDate,
            $techEmails
        );
    
        return view(
            'reports.technicians',
            compact('requests')
        );
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
        // $request->validate([
        //     'from-date' => ['required', 'date'],
        //     'to-date'   => ['required', 'date'],
        // ]);
        $fromDate = request('from-date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = request('to-date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $startDate = Carbon::parse($fromDate)->startOfDay()->format('Y-m-d H:i:s');
        $endDate = Carbon::parse($toDate)->endOfDay()->format('Y-m-d H:i:s');
        // $techEmails = request('tech_emails', []);

        $fileName = 'THỐNG KÊ TỪ '.$fromDate.' ĐẾN '.$toDate;

        return Excel::download(
            new MaintenanceByBranchExport(
                $startDate,
                $endDate,
                // $techEmails
            ),
            $fileName.'.xlsx'
        );
    }

    public function exportKpi()
    {
        $fromDate = request(
            'from_date',
            now()->startOfMonth()->format('Y-m-d')
        );

        $toDate = request(
            'to_date',
            now()->endOfMonth()->format('Y-m-d')
        );

        $techEmails = request(
            'tech_emails',
            []
        );

        return Excel::download(
            new TechnicianKpiExport(
                $fromDate,
                $toDate,
                $techEmails
            ),
            'KPI_Technician_'
            . now()->format('Ymd_His')
            . '.xlsx'
        );
    }
}