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
            'from_date',
            Carbon::now()->startOfMonth()->format('Y-m-d')
        );

        $endDate = request(
            'to_date',
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );

        $startDateCompleted = request(
            'from_date_completed',
            Carbon::now()->startOfDay()->format('Y-m-d')
        );

        $endDateCompleted = request(
            'to_date_completed',
            Carbon::now()->endOfDay()->format('Y-m-d')
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
                $startDateCompleted,
                $endDateCompleted,
                $techEmails
            ),
            $exportName
        );
    }

    public function exportTechs()
    {
        $startDate = request(
            'from_date',
            Carbon::now()->startOfMonth()->format('Y-m-d')
        );

        $endDate = request(
            'to_date',
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );

        $startDateCompleted = request(
            'from_date_completed',
            Carbon::now()->startOfDay()->format('Y-m-d')
        );

        $endDateCompleted = request(
            'to_date_completed',
            Carbon::now()->endOfDay()->format('Y-m-d')
        );

        $techEmails = request('tech_emails', []);

        $exportName =
            now()->format('Ymd_His')
            . '_tech_report.xlsx';

        return Excel::download(
            new TechnicianReportExport(
                $startDate,
                $endDate,
                $startDateCompleted,
                $endDateCompleted,
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
            Carbon::now()->startOfMonth()->format('Y-m-d')
        );

        $endDate = request(
            'to-date',
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );

        $startDateCompleted = request(
            'from-date-completed',
            Carbon::now()->startOfDay()->format('Y-m-d')
        );

        $endDateCompleted = request(
            'to-date-completed',
            Carbon::now()->endOfDay()->format('Y-m-d')
        );
    
        $techEmails = request(
            'tech_emails',
            []
        );
    
        $requests = $service->getReport(
            $startDate,
            $endDate,
            $startDateCompleted,
            $endDateCompleted,
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
        
        $startDate = request(
            'from_date',
            Carbon::now()->startOfMonth()->format('Y-m-d')
        );

        $endDate = request(
            'to_date',
            Carbon::now()->endOfMonth()->format('Y-m-d')
        );

        $startDateCompleted = request(
            'from_date_completed',
            Carbon::now()->startOfDay()->format('Y-m-d')
        );

        $endDateCompleted = request(
            'to_date_completed',
            Carbon::now()->endOfDay()->format('Y-m-d')
        );

        $startDate = Carbon::parse($startDate)->startOfDay()->format('Y-m-d H:i:s');
        $endDate = Carbon::parse($endDate)->endOfDay()->format('Y-m-d H:i:s');
        // $techEmails = request('tech_emails', []);

        $fileName = 'THỐNG KÊ TỪ ' . Carbon::parse($startDate)->format('d/m/Y') . ' ĐẾN ' . Carbon::parse($endDate)->format('d/m/Y');
   

        return Excel::download(
            new MaintenanceByBranchExport(
                $startDate,
                $endDate,
                $startDateCompleted,
                $endDateCompleted,
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