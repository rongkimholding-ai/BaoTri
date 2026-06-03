<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceRequestsExport;
use Maatwebsite\Excel\Facades\Excel;

class MaintenanceController extends Controller
{
    public function export()
    {
        $exportName = rand(1,2000).date('Ymd').'_report.xlsx';
        return Excel::download(
            new MaintenanceRequestsExport(),
            $exportName
        );
    }
}