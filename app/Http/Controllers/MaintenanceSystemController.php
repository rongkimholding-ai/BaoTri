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

        $maintenanceSystem->writeLog(
            action: 'CREATE',
            newStatus: $maintenanceSystem->status,
            note: 'Khởi tạo yêu cầu'
        );

        return redirect()
            ->route('maintenance-system.index')
            ->with('success', 'Tạo yêu cầu thành công.');
    }

    public function show(MaintenanceSystem $maintenanceSystem)
    {
        $maintenanceSystem->load([
            'logs' => function ($query) {
                $query->latest();
            }
        ]);
        return view(
            'system.modals.detail',
            compact('maintenanceSystem')
        );
    }

    public function edit(MaintenanceSystem $maintenanceSystem)
    {
        return view(
            'system.modals.edit',
            [
                'maintenanceSystem' => $maintenanceSystem,
                'stores' => $this->getData(),
                'techs'  => $this->getTechnicianData(),
            ]
        );
    }

    public function update(
        UpdateMaintenanceSystemRequest $request,
        MaintenanceSystem $maintenanceSystem
    ) {
        $oldStatus = $maintenanceSystem->status;
        $data = $request->validated();
    
        $data['updated_by'] = auth()->user()->email;
        $data['updated_at'] = now();
    
        if (
            $maintenanceSystem->status === 'COMPLETED'
            && is_null($maintenanceSystem->completed_at)
        ) {
    
            $data['completed_at'] = now();
    
            $data['completed_by'] = auth()->user()->email;
        }
    
        // dd($data);
        $maintenanceSystem->update($data);

        $maintenanceSystem->writeLog(

            action: 'UPDATE',
        
            oldStatus: $oldStatus,
        
            newStatus: $maintenanceSystem->status,
        
            note: 'Cập nhật thông tin'
        
        );
    
        return redirect()
            ->route('maintenance-system.index')
            ->with('success', 'Cập nhật thành công.');
    }

    public function changeStatusForm(
        MaintenanceSystem $maintenanceSystem,
        string $status
    )
    {
        $workflow = config('maintenance_system.workflow');
    
        abort_unless(
            in_array(
                $status,
                $workflow[$maintenanceSystem->status] ?? []
            ),
            404
        );
    
        return view(
            'system.modals.change-status',
            compact(
                'maintenanceSystem',
                'status'
            )
        );
    }

    public function changeStatus(
        Request $request,
        MaintenanceSystem $maintenanceSystem
    )
    {
        $workflow = config('maintenance_system.workflow');

        $oldStatus = $maintenanceSystem->status;
        
        $validated = $request->validate([
            'status' => ['required'],
            'delay_reason' => ['nullable', 'string'],
            'note' => ['nullable', 'string']
        ]);

        if (! in_array(
            $validated['status'],
            $workflow[$maintenanceSystem->status] ?? []
        )) {
            abort(403);
        }
    
        $data = [
            'status' => $validated['status'],
            'delay_reason' => $validated['note'] ?? null,
            'updated_by' => auth()->user()->email,
        ];
    
        if (
            $validated['status'] === 'COMPLETED'
            && !$maintenanceSystem->completed_at
        ) {
    
            $data['completed_at'] = now();
    
            $data['completed_by'] = auth()->user()->email;
    
        }
        // dd($data);
    
        $maintenanceSystem->update($data);

        $maintenanceSystem->writeLog(

            action: 'CHANGE_STATUS',
        
            oldStatus: $oldStatus,
        
            newStatus: $maintenanceSystem->status,
        
            note: $request->note
        
        );
    
        return redirect()
            ->route('maintenance-system.index')
            ->with('success', 'Đổi trạng thái thành công.');
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
