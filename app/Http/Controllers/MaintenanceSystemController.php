<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceSystemRequest;
use App\Http\Requests\UpdateMaintenanceSystemRequest;
use App\Models\MaintenanceSystem;
use Illuminate\Http\Request;

class MaintenanceSystemController extends Controller
{
    public function index(Request $request)
    {
        $items = MaintenanceSystem::query()

            ->when($request->filled('keyword'), function ($query) use ($request) {

                $keyword = trim($request->keyword);

                $query->where(function ($q) use ($keyword) {

                    $q->where('issue_code', 'like', "%{$keyword}%")
                        ->orWhere('issue_name', 'like', "%{$keyword}%")
                        ->orWhere('branch_code', 'like', "%{$keyword}%")
                        ->orWhere('branch_name', 'like', "%{$keyword}%")
                        ->orWhere('technician_name', 'like', "%{$keyword}%")
                        ->orWhere('technician_email', 'like', "%{$keyword}%");
                });
            })

            ->when($request->filled('status'), function ($query) use ($request) {

                $query->where('status', $request->status);

            })

            ->when($request->filled('from_date'), function ($query) use ($request) {

                $query->whereDate('request_date', '>=', $request->from_date);

            })

            ->when($request->filled('to_date'), function ($query) use ($request) {

                $query->whereDate('request_date', '<=', $request->to_date);

            })

            ->orderByDesc('request_date')
            ->paginate(20)
            ->withQueryString();

        return view('system.index', compact('items'));
    }

    public function create()
    {
        $data = [
            'stores'     => $this->getData(),
            'techs'      => $this->getTechnicianData(),
        ];
        return view('system.modals.create',$data);
    }

    public function store(StoreMaintenanceSystemRequest $request)
    {
        $data = $request->validated();

        $data['created_by'] = auth()->user()->email;
        $data['created_at'] = now();
        $data['request_date'] = now();
        $data['status'] = 'NEW';
        // dd($data);

        $maintenanceSystem = MaintenanceSystem::create($data);

        return redirect()
            ->route('maintenance-system.index')
            ->with('success', 'Tạo yêu cầu thành công.');
    }

    public function show(MaintenanceSystem $maintenanceSystem)
    {
        return view(
            'system.modals.detail',
            compact('maintenanceSystem')
        );
    }

    public function edit(MaintenanceSystem $maintenanceSystem)
    {
        return view(
            'system.modals.edit',
            compact('maintenanceSystem')
        );
    }

    public function update(
        UpdateMaintenanceSystemRequest $request,
        MaintenanceSystem $maintenanceSystem
    ) {
        $data = $request->validated();
    
        $data['updated_by'] = auth()->user()->email;
    
        if (
            $data['status'] === 'COMPLETED'
            && is_null($maintenanceSystem->completed_at)
        ) {
    
            $data['completed_at'] = now();
    
            $data['completed_by'] = auth()->user()->email;
        }
    
        $maintenanceSystem->update($data);
    
        return redirect()
            ->route('maintenance-system.index')
            ->with('success', 'Cập nhật thành công.');
    }

    public function destroy(MaintenanceSystem $maintenanceSystem)
    {
        $maintenanceSystem->delete();

        return back()->with(
            'success',
            'Đã xóa dữ liệu.'
        );
    }

    public function getData()
    {
        $jsonPathNorth = resource_path('json/stores.json');
        $jsonPathSouth = resource_path('json/stores_mn.json');
        $storesNorth = json_decode(file_get_contents($jsonPathNorth), true);
        $storesSouth = json_decode(file_get_contents($jsonPathSouth), true);

        // Tạo cấu trúc rõ 2 miền
        $data = [
            'mien_bac' => $storesNorth,
            'mien_nam' => $storesSouth,
        ];

        return $data;
    }

    public function getTechnicianData()
    {
        $data = config('technician_ht');
        return $data;
    }
}
