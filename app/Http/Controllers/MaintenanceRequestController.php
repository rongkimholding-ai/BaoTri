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
        $requests = MaintenanceRequest::latest()->paginate(20);;

        // $requests = MaintenanceRequest::all();
        $stores = $this->getData();
        $checks = $this->getChecksData();
        $techs = $this->getTechnicianData();
        $severities = $this->getSeveritiesData();


        return view('maintenance.index', compact('requests', 'stores', 'checks', 'techs', 'severities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stores = $this->getData();
        $checks = $this->getChecksData();
        $techs = $this->getTechnicianData();
        $severities = $this->getSeveritiesData();
        dd($severities);

        return view('maintenance.create', compact('checks', 'stores', 'techs', 'severities'));
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

        $item->is_confirmed = filter_var($request->confirmed, FILTER_VALIDATE_BOOLEAN);

        if ($request->confirmed) {
            // Lấy giờ thực tế dạng HH:ii:ss và chuyển đổi sang giây
            $actualDuration = $item->actual_duration; // dạng "HH:ii:ss"
            $actualSeconds = 0;
            if ($actualDuration) {
                list($h, $i, $s) = explode(':', $actualDuration);
                $actualSeconds = ((int)$h) * 3600 + ((int)$i) * 60 + ((int)$s);
            }

            // Lấy danh sách thời gian chuẩn từ file json
            $realTimeList = json_decode(file_get_contents(resource_path('json/real_time.json')), true);
            $realTimeMap = collect($realTimeList)->keyBy('key');

            $stdKey = $item->standard_completion_time;
            $minSeconds = null;
            $maxSeconds = null;
            if ($stdKey && isset($realTimeMap[$stdKey])) {
                $minSeconds = $realTimeMap[$stdKey]['min_seconds'];
                $maxSeconds = $realTimeMap[$stdKey]['max_seconds'];
            }

            // So sánh actualSeconds với min và max (nếu tồn tại)
            if (!is_null($minSeconds) && !is_null($maxSeconds) && $actualSeconds > 0) {
                if ($actualSeconds < $minSeconds || $actualSeconds > $maxSeconds) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Thời gian thực hiện thực tế không hợp lệ so với tiêu chuẩn cho công việc này!'
                    ], 422);
                }
            }


            MaintenanceRequestLog::create([
                'maintenance_request_id' => $item->id,
                'user_id' => auth()->id(),
                'old_status' => $item->sla_status,
                'new_status' => config('sla_status.code.COMPLETED'),
                'note' => 'Xác nhận hoàn thành',
            ]);

            $item->confirmed_at = now();
            $item->delay_reason = '';
            $item->sla_status = config('sla_status.code.COMPLETED');
            $item->acceptance_confirmed_by = auth()->user()->name;
        }
        //  else {
        //     $item->confirmed_at = null;
        //     $item->acceptance_confirmed_by = null;
        // }

        $item->save();

        return response()->json([
            'success' => true,
            'confirmed' => $item->is_confirmed,
            'confirmer' => $item->acceptance_confirmed_by,
            'status' => config('sla_status.code.COMPLETED'),
        ]);
    }

    public function changeStatus(
        Request $request,
        MaintenanceRequest $maintenanceRequest
    ) {
        $allowedStatuses = config('sla_status.code');

        $request->validate([
            'status' => ['string', 'in:' . implode(',', $allowedStatuses)]
        ]);

        // kiểm tra quyền
        if (!auth()->user()->can('change-maintenance-status')) {
            abort(403);
        }
        $oldStatus = $maintenanceRequest->sla_status;

        $data = [
            'sla_status' => $request->status,
            'delay_reason' => $request->note,
        ];

        if ($request->status === config('sla_status.code.WAITING_CONFIRM')) {
            $completedAt = now();
            $seconds = Carbon::parse($maintenanceRequest->request_date)
                ->diffInSeconds($completedAt);

            $data['actual_completion_date'] = $completedAt;
            $data['delay_reason'] = '';
            if ($maintenanceRequest->pending_at && $maintenanceRequest->processing_at) {

                // Có tạm dừng
                $totalSeconds =
                    Carbon::parse($maintenanceRequest->request_date)
                        ->diffInSeconds($maintenanceRequest->pending_at)
                    +
                    Carbon::parse($maintenanceRequest->processing_at)
                        ->diffInSeconds(
                            $maintenanceRequest->actual_completion_date ?? now()
                        );

            } else {

                // Không tạm dừng
                $totalSeconds =
                    Carbon::parse($maintenanceRequest->request_date)
                        ->diffInSeconds(
                            $item->actual_completion_date ?? now()
                        );
            }
            $hours = floor($totalSeconds / 3600);
            $minutes = floor(($totalSeconds % 3600) / 60);
            $seconds = $totalSeconds % 60;

            $data['actual_duration'] = sprintf(
                '%02d:%02d:%02d',
                $hours,
                $minutes,
                $seconds
            );
        }

        if (in_array($request->status,[config('sla_status.code.PENDING'),config('sla_status.code.PENDING_CONTRACTOR')])) {
            $data['pending_at'] = now();
        }
        if ($request->status === config('sla_status.code.CONTINUE_PROCESSING')) {
            $data['processing_at'] = now();
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

    public function getSeveritiesData()
    {
        $jsonPath = resource_path('json/severities.json');
        $data = json_decode(file_get_contents($jsonPath), true);

        return $data;
    }
    public function getActualDurationTextAttribute($actual_duration)
    {
        if (!$actual_duration) {
            return null;
        }

        [$hours, $minutes, $seconds] = explode(':', $actual_duration);

        $days = floor($hours / 24);
        $hours = $hours % 24;

        $parts = [];

        if ($days > 0) {
            $parts[] = "{$days} ngày";
        }

        if ($hours > 0) {
            $parts[] = "{$hours} giờ";
        }

        if ($minutes > 0) {
            $parts[] = "{$minutes} phút";
        }

        if ($seconds > 0) {
            $parts[] = "{$seconds} giây";
        }

        return implode(' ', $parts);
    }
}
