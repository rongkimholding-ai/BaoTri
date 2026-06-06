<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceRequest;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestLog;
use App\Services\MaintenanceRequestService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\MaintenanceReminderMail;
class MaintenanceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $requests = MaintenanceRequest::latest()->get();

        // $requests = MaintenanceRequest::all();
        $stores = $this->getData();
        $checks = $this->getChecksData();
        $techs = $this->getTechnicianData();


        return view('maintenance.index', compact('requests', 'stores', 'checks','techs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = $this->getData();
        $checks = $this->getChecksData();
        $techs = $this->getTechnicianData();

        return view('maintenance.create', compact('checks', 'stores','techs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        StoreMaintenanceRequest $request,
        MaintenanceRequestService $service
    ) {
        $service->create(
            $request->validated()
        );

        return back()->with(
            'success',
            'Thêm thành công'
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(MaintenanceRequest $maintenanceRequest)
    {
        return response()->json($maintenanceRequest);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaintenanceRequest $maintenanceRequest)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        MaintenanceRequest $maintenanceRequest
    ) {
        $maintenanceRequest->update(
            $request->all()
        );

        return back()->with(
            'success',
            'Cập nhật thành công'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(
        MaintenanceRequest $maintenanceRequest
    ) {
        $maintenanceRequest->delete();

        return back()->with(
            'success',
            'Xóa thành công'
        );
    }

    public function inlineUpdate(Request $request)
    {
        abort_unless(
            auth()->user()->can('update data'),
            403
        );

        $item = MaintenanceRequest::findOrFail($request->id);

        $field = $request->field;
        $value = $request->value;

        // handle date
        if ($field === 'request_date' && $value) {
            $value = Carbon::parse($value);
        }

        $item->$field = $value;
        $item->save();

        return response()->json(['success' => true]);
    }

    public function remind(Request $request)
    {
        $item = MaintenanceRequest::findOrFail(
            $request->id
        );

        if (!$item->technician_email) {

            return response()->json([
                'success' => false,
                'message' => 'Chưa khai báo email kỹ thuật viên'
            ], 422);
        }

        Mail::to(
            $item->technician_email
        )->send(
                new MaintenanceReminderMail($item)
            );

        $item->increment('reminder_count');

        $item->update([
            'last_reminded_at' => now()
        ]);

        return response()->json([
            'success' => true
        ]);
    }

    public function confirm(Request $request)
    {
        abort_unless(
            auth()->user()->can('confirm maintenance'),
            403
        );

        $item = MaintenanceRequest::findOrFail(
            $request->id
        );

        $item->is_confirmed = $request->confirmed;

        if ($request->confirmed) {

            $item->confirmed_at = now();

            $item->acceptance_confirmed_by =
                $this->getApproverByBranch(
                    $item->branch_name
                ) ?? auth()->user()->name;

        } else {

            $item->confirmed_at = null;

            $item->acceptance_confirmed_by = null;
        }

        $item->save();

        return response()->json([
            'success' => true,
            'confirmed' => $item->is_confirmed,
            'confirmer' => $item->acceptance_confirmed_by,
        ]);
    }

    public function changeStatus(
        Request $request,
        MaintenanceRequest $maintenanceRequest
    ) {
        $allowedStatuses = config('sla_status');
    
        $request->validate([
            'status' => ['string', 'in:' . implode(',', $allowedStatuses)]
        ]);

        // kiểm tra quyền
        if (!auth()->user()->can('change-maintenance-status')) {
            abort(403);
        }
        $oldStatus = $maintenanceRequest->sla_status;

        $data = [
            'sla_status' => $request->status
        ];
        
        if ($request->status === 'Chờ xác nhận') {
            $completedAt = now();
            $seconds = Carbon::parse($maintenanceRequest->request_date)
                ->diffInSeconds($completedAt);

            $data['actual_completion_date'] = $completedAt;
            $data['actual_duration'] = gmdate('H:i:s', $seconds);
        }
    
        $maintenanceRequest->update($data);
        // dd($maintenanceRequest->update($data));

        MaintenanceRequestLog::create([
            'maintenance_request_id' => $maintenanceRequest->id,
            'user_id' => auth()->id(),
            'old_status' => $oldStatus,
            'new_status' => $request->status,
            'note' => $request->note,
        ]);
    
        return response()->json([
            'success' => true,
            'sla_status' => $maintenanceRequest->status
        ]);
    }

    public function logs(MaintenanceRequest $maintenanceRequest)
    {
        return response()->json(
            $maintenanceRequest->logs()
                ->with('user')
                ->get()
        );
    }

    private function getApproverByBranch($branchName)
    {
        $jsonPath = resource_path('json/stores.json');
        $data = json_decode(file_get_contents($jsonPath), true);

        // Lặp qua từng phần tử trong $data để tìm name == $branchName và lấy om_name
        $mapping = [];
        foreach ($data as $store) {
            if (isset($store['name']) && isset($store['om_name'])) {
                $mapping[$store['name']] = $store['om_name'];
            }
        }

        return $mapping[$branchName] ?? null;
    }

    public function getData()
    {
        $jsonPath = resource_path('json/stores.json');
        $data = json_decode(file_get_contents($jsonPath), true);

        return $data;
    }

    public function getChecksData()
    {
        $jsonPath = resource_path('json/checks.json');
        $data = json_decode(file_get_contents($jsonPath), true);

        return $data;
    }

    public function getTechnicianData()
    {
        $jsonPath = resource_path('json/technician.json');
        $data = json_decode(file_get_contents($jsonPath), true);

        return $data;
    }
}
