<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeMaintenanceStatusRequest;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Jobs\UploadMaintenanceImagesJob;
use App\Mail\MaintenanceAcceptanceMail;
use App\Mail\MaintenanceBuyerMail;
use App\Mail\MaintenanceCompletedMail;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestImage;
use App\Models\MaintenanceRequestLog;
use App\Models\MtUpdateLog;
use App\Models\MtUpdateLogDetail;
use App\Services\MaintenanceImageService;
use App\Services\MaintenanceRequestService;
use App\Services\MaintenanceStatusService;
use App\Services\SlaCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\MaintenanceReminderMail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MaintenanceRequestController extends Controller
{
    public function __construct(
        protected MaintenanceImageService $imageService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        session(['current_module' => 'facility']);
        $user = auth()->user();
        $role = $user->getRoleNames()->first();
        $email = strtolower($user->email);
        $baseQuery = MaintenanceRequest::query();

        // ----------------------------------------
        // Data access control
        // ----------------------------------------
        switch ($role) {
            case 'technician':
                $baseQuery->where(function ($q) use ($user) {
                    $q->where('technician_email', $user->email)
                        ->orWhere('created_by', $user->email);
                });
                break;
            case 'user':
                $baseQuery->where(function ($q) use ($user) {
                    $q->where('branch_email', $user->email)
                        ->orWhere('created_by', $user->email);
                });
                break;
            case 'manager':
            case 'am':
            case 'om':
            case 'viewer':
                // Allow special user to see all (for managers, am, om roles)
                if (!in_array($email, config('special_user.full_view'))) {
                    // lấy theo Store từ DB
                    $stores = \App\Models\Store::all()->toArray();

                    $emails = collect($stores)
                        ->filter(function ($store) use ($email) {
                            return (
                                (isset($store['om_email']) && strtolower($store['om_email']) == $email) ||
                                (isset($store['am_email']) && strtolower($store['am_email']) == $email)
                            ) && isset($store['email']);
                        })
                        ->pluck('email')
                        ->unique()
                        ->values()
                        ->all();

                    $baseQuery->where(function ($q) use ($emails, $user) {
                        if (!empty($emails)) {
                            $q->whereIn('branch_email', $emails);
                        } else {
                            $q->whereRaw('1=0');
                        }
                        // OR created_by current user
                        $q->orWhere('created_by', $user->email);
                    });
                }
                // else: allow all
                break;

            case 'muasam':
                $baseQuery->where('sla_status', config('sla_status.code.PENDING'));
                break;
            // admin and others: no restriction
        }

        // ----------------------------------------
        // Filter processing
        // ----------------------------------------
        // Xử lý nhận giá trị mặc định ban đầu cho from_date và to_date (đầu/cuối tháng nếu không truyền lên)
        $defaultFromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $defaultToDate = Carbon::now()->endOfMonth()->format('Y-m-d');

        $filters = [
            'from_date' => function ($q, $v) use ($defaultFromDate) {
                // Nếu không có giá trị (không search), dùng ngày đầu tháng
                $date = $v ?: $defaultFromDate;
                if ($date) $q->where('request_date', '>=', Carbon::parse($date)->startOfDay());
            },
            'to_date' => function ($q, $v) use ($defaultToDate) {
                // Nếu không có giá trị (không search), dùng ngày cuối tháng
                $date = $v ?: $defaultToDate;
                if ($date) $q->where('request_date', '<=', Carbon::parse($date)->endOfDay());
            },
            'from_date_completed' => function ($q, $v) {
                if ($v) $q->where('actual_completion_date', '>=', Carbon::parse($v)->startOfDay());
            },
            'to_date_completed' => function ($q, $v) {
                if ($v) $q->where('actual_completion_date', '<=', Carbon::parse($v)->endOfDay());
            },
            'branch_code' => fn($q, $v) => $q->where('branch_code', 'like', "%$v%"),
            'branch_name' => fn($q, $v) => $q->where('branch_name', 'like', "%$v%"),
            'severity'    => fn($q, $v) => $q->where('severity', $v),
            'status'      => fn($q, $v) => $q->where('sla_status', $v),
            'id'          => fn($q, $v) => $q->where('id', $v),
        ];
        foreach ($filters as $field => $filter) {
            // from_date & to_date: chèn mặc định nếu không search
            if (in_array($field, ['from_date', 'to_date'])) {
                $filter($baseQuery, $request->input($field));
            } else {
                if ($request->filled($field)) {
                    $filter($baseQuery, $request->$field);
                }
            }
        }

        $completedStatus = config('sla_status.code.COMPLETED');
        $latedStatus = config('sla_status.code.LATED');
        $newStatus = config('sla_status.code.NEW');
        $severityOrder = collect(config('severities'))->pluck('key')->all();
        $severityOrderStr = implode("','", $severityOrder);

        // Order helper
        $addOrderBySeverity = fn($query) =>
            $query->orderByRaw("FIELD(severity, '$severityOrderStr')")->orderByDesc('id');

        // ----------------------------------------
        // Paginations and counts (only clone once per major query type)
        // ----------------------------------------
        $baseQueryClone = fn() => clone $baseQuery;

        $allRequests = $addOrderBySeverity($baseQueryClone())->paginate(20, ['*'], 'all_page')->withQueryString();

        $processingRequests = $addOrderBySeverity(
            tap($baseQueryClone(), function ($q) use ($completedStatus, $latedStatus, $newStatus) {
                $q->whereNotIn('sla_status', [$completedStatus, $latedStatus, $newStatus])
                    ->where('is_confirmed', '!=', true);
            })
        )->paginate(20, ['*'], 'processing_page')->withQueryString();

        $completedRequests = $addOrderBySeverity(
            tap($baseQueryClone(), function ($q) {
                $q->where('is_confirmed', true);
            })
        )->paginate(20, ['*'], 'completed_page')->withQueryString();

        // Use more efficient count queries (remove unnecessary withNotNull .etc)
        $totalCount = $baseQueryClone()->count();

        $processingCount = $baseQueryClone()
            ->whereNotNull('technician_name')
            ->whereNotIn('sla_status', [$completedStatus, $latedStatus, $newStatus])
            ->where('is_confirmed', '!=', true)
            ->count();

        $completedCount = $baseQueryClone()
            ->where('is_confirmed', true)
            ->count();

        // Data for selects
        $stores     = $this->getData();
        $checks     = $this->getChecksData();
        $techs      = $this->getTechnicianData();
        $severities = $this->getSeveritiesData();

        return view('maintenance.index', compact(
            'allRequests',
            'processingRequests',
            'completedRequests',
            'stores',
            'checks',
            'techs',
            'severities',
            'totalCount',
            'processingCount',
            'completedCount'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $data = [
            'stores'     => $this->getData(),
            'checks'     => $this->getChecksData(),
            'techs'      => $this->getTechnicianData(),
            'severities' => $this->getSeveritiesData(),
        ];

        return view('maintenance.create', $data);
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

        // Tự động cập nhật sla_status thành PROCESSING sau khi tạo xong và lưu vào logs trạng thái với note là "auto tiếp nhận thực hiện"
        $created = MaintenanceRequest::latest()->first();
        if ($created) {
            $created->sla_status = config('sla_status.code.PROCESSING');
            $created->save();

            MaintenanceRequestLog::create([
                'maintenance_request_id' => $created->id,
                'user_id' => auth()->id(),
                'old_status' => config('sla_status.code.NEW'),
                'new_status' => config('sla_status.code.PROCESSING'),
                'note' => 'auto tiếp nhận thực hiện',
            ]);
        }

        // Tự động gửi mail nhắc việc cho kỹ thuật viên khi tạo mới
        if (!empty($created->technician_email)) {
            try {
                $sendMail = $created->technician_email;
                \Mail::to($sendMail)->queue(new MaintenanceReminderMail($created));
                $created->increment('reminder_count', 1, ['last_reminded_at' => now()]);
            } catch (\Throwable $e) {
                \App\Services\LogService::error('maintenance',"AUTO SLA SYSTEM", [
                    'time' => microtime(true),
                    'request_id' => $created->id,
                    'email' => $created->technician_email,
                    'error' => $e->getMessage(),
                ]);
                // \Log::error('Failed to send maintenance reminder email', [
                //     'id' => $created->id,
                //     'email' => $created->technician_email,
                //     'error' => $e->getMessage(),
                // ]);
            }
        }


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
        $maintenanceRequest->load([
            'images',
            'logs.user',
            'updateLogs.user',
            'updateLogs.details',
        ]);

        $title = 'Chi tiết yêu cầu #' . $maintenanceRequest->id;

        $checks = $this->getChecksData();
        $severities = $this->getSeveritiesData();

        return view(
            'maintenance.show',
            compact('maintenanceRequest', 'title', 'checks', 'severities')
        );
    }

    public function detail(MaintenanceRequest $maintenanceRequest)
    {
        // Lấy đầy đủ logs với thông tin user
        $maintenanceRequest->load(['images']);
        $logs = $this->logs($maintenanceRequest);


        return view(
            'maintenance.partials.detail',
            compact('maintenanceRequest')
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaintenanceRequest $maintenanceRequest)
    {
        $data = [
            'stores'     => $this->getData(),
            'checks'     => $this->getChecksData(),
            'techs'      => $this->getTechnicianData(),
            'severities' => $this->getSeveritiesData(),
            'maintenanceRequest' => $maintenanceRequest->id,
        ];

        return view('maintenance.modals.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
    Request $request,
    MaintenanceRequest $maintenanceRequest
    ) {
        DB::transaction(function () use ($request, $maintenanceRequest) {

            // Dữ liệu cập nhật
            $data = $request->all();

            // Chuẩn hóa checkbox
            foreach ([
                'include_saturday',
                'include_sunday',
                'include_holiday',
            ] as $field) {
                $data[$field] = $request->boolean($field);
            }

            // Dữ liệu trước khi update
            $oldData = $maintenanceRequest->getOriginal();

            // Update
            $maintenanceRequest->update($data);

            // Chỉ lấy các field thay đổi
            $changes = $maintenanceRequest->getChanges();

            // Danh sách field cần log
            $fields = config('maintenance_log.fields', []);

            $details = [];

            foreach ($changes as $field => $newValue) {

                // Không log field ngoài config
                if (!isset($fields[$field])) {
                    continue;
                }

                $details[] = new MtUpdateLogDetail([
                    'field'      => $field,
                    'field_name' => $fields[$field],
                    'old_value'  => $this->formatLogValue($field, $oldData[$field] ?? null),
                    'new_value'  => $this->formatLogValue($field, $newValue),
                ]);
            }

            // Không có gì thay đổi thì không tạo log
            if (empty($details)) {
                return;
            }

            $log = MtUpdateLog::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'user_id' => auth()->id(),
                'note' => 'Cập nhật yêu cầu bảo trì',
            ]);

            foreach ($details as $detail) {
                $log->details()->save($detail);
            }
        });

        return back()->with(
            'success',
            'Cập nhật thành công'
        );
    }

    /**
     * Format dữ liệu trước khi lưu log
     */
    private function formatLogValue(string $field, $value): string
    {
        // Các field boolean
        if (in_array($field, [
            'include_saturday',
            'include_sunday',
            'include_holiday',
        ], true)) {
            return $value ? 'Có' : 'Không';
        }

        // Null hoặc rỗng
        if ($value === null || $value === '') {
            return '(Trống)';
        }

        return (string) $value;
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
        $item = MaintenanceRequest::findOrFail($request->id);

        if (empty($item->technician_email)) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa khai báo email kỹ thuật viên'
            ], 422);
        }

        try {
            $email = $item->technician_email;

            Mail::to($email)->queue(new MaintenanceReminderMail($item));

            $item->increment('reminder_count');
            $item->update([
                'last_reminded_at' => now()
            ]);

            return response()->json(['success' => true]);

        } catch (\Throwable $e) {

            \Log::error('Failed to send maintenance reminder email', [
                'id' => $item->id,
                'email' => $item->technician_email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gửi email thất bại. Vui lòng thử lại.'
            ], 500);
        }
    }

    public function confirm(Request $request)
    {
        // Check permission
        if (!auth()->user()->can('confirm maintenance')) {
            abort(403);
        }

        $item = MaintenanceRequest::findOrFail($request->id);
        $item->is_confirmed = filter_var($request->confirmed, FILTER_VALIDATE_BOOLEAN);

        if ($item->is_confirmed) {
            // Parse actual_duration (format: HH:ii:ss)
            $actualSeconds = 0;
            if (!empty($item->actual_duration)) {
                [$h, $i, $s] = array_map('intval', explode(':', $item->actual_duration . '::'));
                $actualSeconds = $h * 3600 + $i * 60 + $s;
            }

            // Get time standard from config
            $stdKey = $item->standard_completion_time;
            $realTime = collect(config('real_time'))->keyBy('key');
            $maxSeconds = $realTime[$stdKey]['max_seconds'] ?? null;

            // Validate against standard time if needed
            if ($maxSeconds !== null && $actualSeconds > 0 && $actualSeconds > $maxSeconds) {
                return response()->json([
                    'success' => false,
                    'message' => 'Thời gian thực hiện thực tế không hợp lệ so với tiêu chuẩn cho công việc này!'
                ], 422);
            }

            // Log status change
            MaintenanceRequestLog::create([
                'maintenance_request_id' => $item->id,
                'user_id' => auth()->id(),
                'old_status' => $item->sla_status,
                'new_status' => config('sla_status.code.COMPLETED'),
                'note' => 'Xác nhận hoàn thành',
            ]);

            $item->fill([
                'confirmed_at' => now(),
                'delay_reason' => '',
                'sla_status' => config('sla_status.code.COMPLETED'),
                'acceptance_confirmed_by' => auth()->user()->name,
                'acceptance_result' => 'accepted'
            ]);
        }
        $item->save();

        return response()->json([
            'success' => true,
            'confirmed' => $item->is_confirmed,
            'confirmer' => $item->acceptance_confirmed_by,
            'status_name' => config('sla_status.names.COMPLETED'),
            'badge_class' => config('sla_status.badge.COMPLETED'),
        ]);
    }

    /**
     * Tối ưu xử lý thay đổi trạng thái cho web & mobile, tránh block UI mobile lâu hoặc treo.
     */
    // public function changeStatus(
    //     ChangeMaintenanceStatusRequest $request,
    //     MaintenanceRequest $maintenanceRequest
    // ) {
    //     \App\Services\LogService::maintenance("ENTER changeStatus", [
    //         'time' => microtime(true),
    //     ]);
   
    //     $validated = $request->validated();
    //     $status = $validated['status'];
    //     $oldStatus = $maintenanceRequest->sla_status;
    //     $now = now();
    //     $statusConfig = config('sla_status.code');
    //     $user = auth()->user();
    //     $start = microtime(true);

    //     $data = [
    //         'sla_status'   => $status,
    //         'delay_reason' => $request->note,
    //     ];

    //     // Tối ưu riêng trạng thái WAITING_CONFIRM (giao diện mobile hay bị treo do tính toán lâu)
    //     if ($status === $statusConfig['WAITING_CONFIRM']) {
    //         $completedAt = $now;

    //         $data['actual_completion_date'] = $completedAt;
    //         $data['delay_reason'] = '';

    //         // Thay đổi: Không thực hiện tính toán SlaCalculatorService trực tiếp (có thể chậm), lưu lại trạng thái, dữ liệu còn lại xử lý async phía sau
    //         $data['actual_duration'] = null; // Bỏ tính sync, sẽ update duration ở tiến trình nền/queue sau
    //         // Web: thử tính luôn, nếu lỗi -> fallback
    //         try {
    //             $data['actual_duration'] = app(SlaCalculatorService::class)
    //                 ->calculate($maintenanceRequest, $completedAt);
    //         } catch (\Throwable $e) {
    //             \App\Services\LogService::error('maintenance','SLA duration calculation fallback (web)', [
    //                 'request_id' => $maintenanceRequest->id,
    //                 'error' => $e->getMessage(),
    //             ]);
    //             $data['actual_duration'] = '00:00:00';
    //         }

    //         // Xử lý kỹ thuật viên chọn ngoài giờ nếu là mail bảo trì
    //         if ($user->email === 'baotri@tocotocotea.com') {
    //             $data['is_off_worktime'] = true;
    //             // Lấy kỹ thuật viên chọn từ form (mobile/web)
    //             $selectedTechEmail = $request->input('tech_mail');
    //             if ($selectedTechEmail) {
    //                 $selectedTech = null;

    //                 foreach (config('technician', []) as $tech) {
    //                     if (($tech['email'] ?? null) === $selectedTechEmail) {
    //                         $selectedTech = $tech;
    //                         break;
    //                     }
    //                 }

    //                 if ($selectedTech) {
    //                     $data['technician_email'] = $selectedTech['email'];
    //                     $data['technician_name'] = $selectedTech['name'];
    //                     $data['technician_mobile'] = $selectedTech['mobile'];
    //                 }
    //             }
    //         }
    //     }

    //     // Các trạng thái khác xử lý như cũ
    //     if ($status === $statusConfig['CONFIRMED']) {
    //         $data['sla_status'] = $this->determineSlaStatus($maintenanceRequest);
    //     }

    //     if ($status === $statusConfig['PENDING']) {
    //         $data['pending_at'] = $now;
    //     }
    //     if ($status === $statusConfig['PENDING_CONTRACTOR']) {
    //         $data['pending_at'] = $now;
    //     }
    //     if ($status === $statusConfig['CONTINUE_PROCESSING']) {
    //         $data['processing_at'] = $now;
    //     }
    //     if ($status === config('sla_status.code.REOPEN')) {
    //         $data = array_merge($data, [
    //             'acceptance_result' => null,
    //             'acceptance_note' => null,
    //             'acceptance_confirmed_by' => null,
    //             'confirmed_at' => null,
    //             'is_confirmed' => false,
    //         ]);
    //     }

    //     \App\Services\LogService::maintenance('UPDATE TRANSACTION START', [
    //         'data' => $data,
    //         'old_status' => $oldStatus,
    //         'new_status' => $status,
    //         'request_id' => $maintenanceRequest->id,
    //     ]);

    //     DB::transaction(function () use ($maintenanceRequest, $data, $oldStatus, $status, $request) {
    //         $maintenanceRequest->update($data);

    //         MaintenanceRequestLog::create([
    //             'maintenance_request_id' => $maintenanceRequest->id,
    //             'user_id'                => auth()->id(),
    //             'old_status'             => $oldStatus,
    //             'new_status'             => $status,
    //             'note'                   => $request->note,
    //         ]);
    //     });

    //     \App\Services\LogService::maintenance('UPDATE DONE', [
    //         'duration' => microtime(true) - $start,
    //         'request_id' => $maintenanceRequest->id,
    //     ]);
    //     $maintenanceRequest->refresh();

    //     DB::afterCommit(function() use ($maintenanceRequest, $status, $request) {
    //         // Web vẫn xử lý synchronous
    //         $this->afterStatusChanged(
    //             $maintenanceRequest,
    //             $status,
    //             $request
    //         );
    //     });

    //     \App\Services\LogService::maintenance('AFTER STATUS DONE', [
    //         'duration' => microtime(true) - $start,
    //         'request_id' => $maintenanceRequest->id,
    //     ]);

    //     $response = response()->json([
    //         'success' => true,
    //         'sla_status' => $maintenanceRequest->sla_status,
    //     ]);

    //     \App\Services\LogService::maintenance('RETURN RESPONSE', [
    //         'status' => $response->status(),
    //         'request_id' => $maintenanceRequest->id,
    //     ]);
    //     \App\Services\LogService::maintenance("LEAVE changeStatus", [
    //         'time' => microtime(true),
    //     ]);

    //     return $response;
    // }

    public function changeStatus(
    ChangeMaintenanceStatusRequest $request,
    MaintenanceRequest $maintenanceRequest,
    MaintenanceStatusService $service
) {
    return response()->json(
        $service->changeStatus(
            $maintenanceRequest,
            $request
        )
    );
}

    // private function afterStatusChanged(
    //     MaintenanceRequest $maintenanceRequest,
    //     string $requestedStatus,
    //     ChangeMaintenanceStatusRequest $request
    // ): void {

    //     // Xử lý theo trạng thái thực tế sau update
    //     if ($requestedStatus === config('sla_status.code.WAITING_CONFIRM')) {
    //         $this->autoConfirm($maintenanceRequest);
    //     }

    //     // Upload ảnh
    //     $this->handleUploadImages(
    //         $maintenanceRequest,
    //         $requestedStatus,
    //         $request
    //     );

    //     // Gửi mail
    //     $this->handleSendMail(
    //         $maintenanceRequest,
    //         $requestedStatus
    //     );
    // }

    public function acceptance(Request $request)
    {
        abort_unless(auth()->user()->can('confirm maintenance'), 403);

        $request->validate([
            'id'     => 'required',
            'result' => 'required|in:accepted,rejected',
            'note'   => 'nullable|string|max:1000',

            'images'   => 'nullable|array',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $item = MaintenanceRequest::findOrFail($request->id);

        if (!in_array($item->sla_status, [
            config('sla_status.code.COMPLETED'),
            config('sla_status.code.LATED')
        ])) {
            return response()->json([
                'success' => false,
                'message' => 'Yêu cầu chưa đủ điều kiện nghiệm thu.'
            ], 422);
        }

        if ($request->result === 'rejected' && blank($request->note)) {
            return response()->json([
                'success' => false,
                'message' => 'Vui lòng nhập lý do không đạt.'
            ], 422);
        }

        DB::transaction(function () use ($request, $item) {

            $item->acceptance_result       = $request->result;
            $item->acceptance_note         = $request->note;
            $item->acceptance_confirmed_by = auth()->user()->name;
            $item->confirmed_at            = now();
            $item->is_confirmed            = ($request->result === 'accepted');

            if ($request->result === 'rejected') {

                $realTimeMap = collect(config('real_time'))->keyBy('key');
                $sla = $realTimeMap[$item->standard_completion_time] ?? null;

                $elapsedSeconds = Carbon::parse($item->request_date)
                    ->diffInSeconds(now());

                $isOverdue = $sla && $elapsedSeconds > (int) $sla['max_seconds'];

                if ($isOverdue) {
                    $item->sla_status = config('sla_status.code.LATED');
                } else {
                    $item->is_confirmed            = false;
                    $item->acceptance_result       = null;
                    $item->acceptance_note         = null;
                    $item->acceptance_confirmed_by = null;
                    $item->confirmed_at            = null;
                    $item->sla_status              = config('sla_status.code.REOPEN');
                }
            }

            $item->save();

            MaintenanceRequestLog::create([
                'maintenance_request_id' => $item->id,
                'user_id'                => auth()->id(),
                'old_status'             => $item->sla_status,
                'new_status'             => $item->sla_status,
                'note'                   => $request->note ?: ($request->result === 'accepted'
                                            ? 'Nghiệm thu đạt' : 'Nghiệm thu không đạt'),
                // 'note'                   => $request->result === 'accepted'
                //                             ? 'Nghiệm thu đạt'
                //                             : ('Nghiệm thu không đạt: ' . $request->note),
            ]);
        });

        // refresh để lấy data mới
        $item->refresh();

        /*
        |--------------------------------------------------------------------------
        | 1. UPLOAD ẢNH → QUEUE (KHÔNG làm trong request nữa)
        |--------------------------------------------------------------------------
        */
        if ($request->hasFile('images')) {

            $tempFiles = [];

            foreach ($request->file('images') as $file) {

                $name = Str::uuid() . '.' . $file->getClientOriginalExtension();

                $file->storeAs('temp-maintenance', $name);

                $tempFiles[] = $name;
            }

            UploadMaintenanceImagesJob::dispatch(
                $item->id,
                $tempFiles,
                auth()->user()->email
            );
        }

        /*
        |--------------------------------------------------------------------------
        | 2. SEND MAIL → QUEUE (KHÔNG send sync)
        |--------------------------------------------------------------------------
        */
        try {
            Mail::to($item->technician_email)
                ->queue(new MaintenanceAcceptanceMail($item));

        } catch (\Throwable $e) {
            // \Log::error('Failed to queue acceptance email', [
            //     'id' => $item->id,
            //     'error' => $e->getMessage(),
            // ]);
            \App\Services\LogService::error('queue',"Failed to queue acceptance email", [
                    'time' => microtime(true),
                    'request_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);
        }

        return response()->json([
            'success' => true
        ]);
    }

    public function destroyImage(
        MaintenanceRequestImage $image
    ) {

        abort_unless(
            auth()->user()->hasRole('admin'),
            403
        );

        try {

            if (
                $image->path &&
                Storage::disk('public')->exists($image->path)
            ) {
                Storage::disk('public')->delete($image->path);
            }

            $image->delete();

            return response()->json([
                'success' => true,
                'message' => 'Đã xóa hình ảnh'
            ]);

        } catch (\Throwable $e) {

            // \Log::error('Delete image failed', [
            //     'image_id' => $image->id,
            //     'error' => $e->getMessage(),
            // ]);
            \App\Services\LogService::error('maintenace',"Delete image failed", [
                    'time' => microtime(true),
                    'image_id' => $image->id,
                    'error' => $e->getMessage(),
                ]);

            return response()->json([
                'success' => false,
                'message' => 'Xóa ảnh thất bại'
            ], 500);
        }
    }

    /**
     * Cập nhật tên, email và số điện thoại của kỹ thuật viên cho MaintenanceSystem.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateTechnicianInfo(Request $request, $id)
    {
        $item = MaintenanceRequest::findOrFail($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bản ghi.'
            ], 404);
        }

        $validated = $request->validate([
            'technician_name'   => 'required|string|max:255',
            'technician_email'  => 'required|email|max:255',
            'technician_mobile' => 'nullable|string|max:30',
        ]);

        $item->technician_name   = $validated['technician_name'];
        $item->technician_email  = $validated['technician_email'];
        $item->technician_mobile = $validated['technician_mobile'] ?? null;
        $item->save();

        return response()->json([
            'success' => true,
            'technician_name'   => $item->technician_name,
            'technician_email'  => $item->technician_email,
            'technician_mobile' => $item->technician_mobile,
        ]);
    }

    public function logs(MaintenanceRequest $maintenanceRequest)
    {
        return response()->json(
            $maintenanceRequest->logs()
                ->with('user')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get()
        );
    }

    public function getData()
    {
        // Lấy dữ liệu theo giá trị trong db của Store (model tại app/Models/Store.php)
        // Đưa về dạng phân chia theo area là 'north' hoặc 'south'
        $stores = \App\Models\Store::all()->toArray();

        $groupedStores = [
            'mien_bac' => [],
            'mien_nam' => [],
        ];

        foreach ($stores as $store) {
            $area = strtolower($store['area'] ?? '');
            if ($area === 'north') {
                $groupedStores['mien_bac'][] = $store;
            } elseif ($area === 'south') {
                $groupedStores['mien_nam'][] = $store;
            }
        }

        $data = $groupedStores;

        return $data;
    }

    public function getChecksData()
    {
        $jsonPath = resource_path('json/checks.json');
        $data = json_decode(file_get_contents($jsonPath), true);
        // Sắp xếp lại tất cả các issues trong data theo thứ tự của severity trong config
        if (is_array($data)) {
            // Lấy thứ tự severity từ config
            $severityOrder = array_map(function ($item) {
                return $item['key'];
            }, config('severities', []));
            $severityOrderFlipped = array_flip($severityOrder);

            // Duyệt từng category trong data và sort issues
            foreach ($data as &$category) {
                if (isset($category['issues']) && is_array($category['issues'])) {
                    usort($category['issues'], function ($a, $b) use ($severityOrderFlipped) {
                        $aSev = $a['severity'] ?? null;
                        $bSev = $b['severity'] ?? null;

                        $aIndex = ($aSev && isset($severityOrderFlipped[$aSev])) ? $severityOrderFlipped[$aSev] : PHP_INT_MAX;
                        $bIndex = ($bSev && isset($severityOrderFlipped[$bSev])) ? $severityOrderFlipped[$bSev] : PHP_INT_MAX;
                        return $aIndex <=> $bIndex;
                    });
                }
            }
            unset($category); // Unset reference
        }

        return $data;
    }

    public function getTechnicianData()
    {
        $data = config('technician');
        return $data;
    }

    public function getSeveritiesData()
    {
        $data = config('severities');
        return $data;
    }

    // private function determineSlaStatus(MaintenanceRequest $item): string
    // {

    //     if (!$item->actual_duration) {
    //         return config('sla_status.code.LATED');
    //     }

    //     [$h, $i, $s] = explode(':', $item->actual_duration);

    //     $actualSeconds = ((int) $h * 3600) + ((int) $i * 60) + (int) $s;

    //     $realTimeList = config('real_time');
    //     $realTimeMap = collect($realTimeList)->keyBy('key');
    //     $stdKey = $item->standard_completion_time;

    //     if (!$stdKey || !isset($realTimeMap[$stdKey])) {
    //         return config('sla_status.code.LATED');
    //     }

    //     $maxSeconds = $realTimeMap[$stdKey]['max_seconds'];

    //     return $actualSeconds <= $maxSeconds
    //         ? config('sla_status.code.COMPLETED')
    //         : config('sla_status.code.LATED');
    // }

    // private function autoConfirm(MaintenanceRequest $maintenanceRequest): void
    // {
    //     $maintenanceRequest->refresh();
    //     $newStatus = $this->determineSlaStatus($maintenanceRequest);

    //     if ($maintenanceRequest->sla_status === $newStatus) {
    //         return;
    //     }

    //     DB::transaction(function () use ($maintenanceRequest, $newStatus) {

    //         $maintenanceRequest->update([
    //             'sla_status' => $newStatus,
    //         ]);

    //         MaintenanceRequestLog::create([
    //             'maintenance_request_id' => $maintenanceRequest->id,
    //             'user_id' => 1,
    //             'old_status' => config('sla_status.code.WAITING_CONFIRM'),
    //             'new_status' => $newStatus,
    //             'note' => 'Auto duyệt yêu cầu',
    //         ]);
    //     });
    // }

    // private function handleUploadImages(
    //     MaintenanceRequest $maintenanceRequest,
    //     string $requestedStatus,
    //     ChangeMaintenanceStatusRequest $request
    // ): void {
    //     $time = microtime(true);
    //     if ($request->hasFile('images')) {
    //         $filesInfo = collect($request->file('images'))
    //             ->map(function ($f) {
    //                 return [
    //                     'size' => $f->getSize(),
    //                     'name' => $f->getClientOriginalName()
    //                 ];
    //             });
            
    //         \App\Services\LogService::queue('Danh sách file upload:', [
    //             'duration' => microtime(true) - $time,
    //             'request_id' => $maintenanceRequest->id,
    //             'FILES' => $filesInfo,
    //         ]);
    //     }
   
    //     if (
    //         $requestedStatus !== config('sla_status.code.WAITING_CONFIRM')
    //         || !$request->hasFile('images')
    //     ) {
    //         return;
    //     }

    //     $tempFiles = [];
    //     $files = $request->file('images', []);

    //     foreach ($files as $file) {

    //         $tempName = Str::uuid() . '.' . $file->extension();

    //         $file->storeAs(
    //             'temp-maintenance',
    //             $tempName
    //         );

    //         $tempFiles[] = $tempName;
    //     }

    //     UploadMaintenanceImagesJob::dispatch(
    //         $maintenanceRequest->id,
    //         $tempFiles,
    //         $request->input('technician_mail')
    //         ?: auth()->user()->email
    //     );
    //     \App\Services\LogService::queue('UPLOAD TIME', [
    //         'duration' => microtime(true) - $time,
    //         'request_id' => $maintenanceRequest->id,
    //     ]);
    // }

    // private function handleSendMail(
    //     MaintenanceRequest $maintenanceRequest,
    //     string $requestedStatus
    // ): void {
    //     $time = microtime(true);
    //     try {

    //         switch ($requestedStatus) {

    //             case config('sla_status.code.WAITING_CONFIRM'):

    //                 Mail::to($maintenanceRequest->branch_email)
    //                     ->queue(new MaintenanceCompletedMail($maintenanceRequest));

    //                 break;

    //             case config('sla_status.code.PENDING'):

    //                 Mail::to($maintenanceRequest->technician_email)
    //                     ->queue(new MaintenanceBuyerMail($maintenanceRequest));

    //                 break;
    //         }

    //     } catch (\Throwable $e) {
    //         \App\Services\LogService::error('queue', 'Send mail failed', [
    //             'duration' => microtime(true) - $time,
    //             'request_id' => $maintenanceRequest->id,
    //             'status' => $requestedStatus,
    //             'exception' => $e->getMessage(),
    //         ]);
    //     }
    //     \App\Services\LogService::queue('MAIL TIME', [
    //         'duration' => microtime(true) - $time,
    //         'request_id' => $maintenanceRequest->id,
    //     ]);
    // }
}
