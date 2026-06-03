<?php

namespace App\Http\Controllers;

use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\Http;

abstract class Controller
{
    //
    public function index()
{
    $data = MaintenanceRequest::latest()->get();

    return view(
        'maintenance.index',
        compact('data')
    );
}
}
