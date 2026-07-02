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
use App\Services\MaintenanceImageService;
use App\Services\MaintenanceRequestService;
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
        $user = auth()->user();
        $role = $user->getRoleNames()->first();
        $email = strtolower($user->email);
        $baseQuery = MaintenanceRequest::query();

        // ----------------------------------------
        // Data access control
        // ----------------------------------------
        switch ($role) {
            case 'technician':
                $baseQuery->where('technician_email', $user->email);
                break;
            case 'user':
                $baseQuery->where('branch_email', $user->email);
                break;
            case 'manager':
            case 'am':
            case 'om':
            case 'viewer':
                // Allow special user to see all (for managers, am, om roles)
                if (!in_array($email, config('special_user.full_view'))) {
                    $jsonPaths = [
                        resource_path('json/stores.json'),
                        resource_path('json/stores_mn.json'),
                        resource_path('json/stores_cici_mb.json'),
                        resource_path('json/stores_cici_mn.json'),
                    ];
                    $stores = [];
                    foreach ($jsonPaths as $path) {
                        if (is_file($path)) {
                            $arr = json_decode(file_get_contents($path), true);
                            if (is_array($arr)) {
                                $stores = array_merge($stores, $arr);
                            }
                        }
                    }
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

                    $baseQuery->when(!empty($emails),
                        fn($q) => $q->whereIn('branch_email', $emails),
                        fn($q) => $q->whereRaw('1=0')
                    );
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
        $filters = [
            'from_date' => function ($q, $v) {
                if ($v) $q->where('request_date', '>=', Carbon::parse($v)->startOfDay());
            },
            'to_date' => function ($q, $v) {
                if ($v) $q->where('request_date', '<=', Carbon::parse($v)->endOfDay());
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
            if ($request->filled($field)) {
                $filter($baseQuery, $request->$field);
            }
        }

        $completedStatus = config('sla_status.code.COMPLETED');
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
            tap($baseQueryClone(), function ($q) use ($completedStatus, $newStatus) {
                $q->whereNotIn('sla_status', [$completedStatus, $newStatus])
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
            ->whereNotIn('sla_status', [$completedStatus, $newStatus])
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
        // dd($stores);

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
                \Log::error('Failed to send maintenance reminder email', [
                    'id' => $created->id,
                    'email' => $created->technician_email,
                    'error' => $e->getMessage(),
                ]);
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
        ]);

        $title = 'Chi tiết yêu cầu #' . $maintenanceRequest->id;
    
        return view(
            'maintenance.show',
            compact('maintenanceRequest','title')
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

    public function changeStatus(
        ChangeMaintenanceStatusRequest $request,
        MaintenanceRequest $maintenanceRequest
    )
    {
        $validated = $request->validated();
        $status = $validated['status'];
        $oldStatus = $maintenanceRequest->sla_status;
        $now = now();

        $data = [
            'sla_status'   => $status,
            'delay_reason' => $request->note,
        ];

        switch ($status) {
            case config('sla_status.code.WAITING_CONFIRM'):
                $completedAt = $now;
                $data['actual_completion_date'] = $completedAt;
                $data['delay_reason'] = '';

                $data['actual_duration'] = app(SlaCalculatorService::class)
                ->calculate(
                    $maintenanceRequest,
                    $completedAt
                );

                if (auth()->user()->email === 'baotri@tocotocotea.com') {
                    $data['is_off_worktime'] = true;
                    // Get selected technician email from request
                    $selectedTechEmail = $request->input('tech_mail');
                    if ($selectedTechEmail) {
                        $technicians = config('technician');
                        // Remove any non-numeric key (like 'ngoai_gio')
                        $techList = array_filter($technicians, function ($key) {
                            return is_int($key) || ctype_digit((string) $key);
                        }, ARRAY_FILTER_USE_KEY);

                        // Find technician matching selected email
                        $selectedTech = collect($techList)->first(function ($tech) use ($selectedTechEmail) {
                            return isset($tech['email']) && $tech['email'] === $selectedTechEmail;
                        });
                        if ($selectedTech) {
                            $data['technician_email'] = $selectedTech['email'];
                            $data['technician_name'] = $selectedTech['name'];
                            $data['technician_mobile'] = $selectedTech['mobile'];
                        }
                    }
                }

                break;

            case config('sla_status.code.CONFIRMED'):
                $data['sla_status'] = $this->determineSlaStatus($maintenanceRequest);
                break;
        }

        if ($status === config('sla_status.code.PENDING')) {
            $data['pending_at'] = $now;
            // Gửi mail thông báo khi mua sắm xong
            try {
                Mail::to($maintenanceRequest->technician_email)
                    ->queue(new MaintenanceBuyerMail($maintenanceRequest));
            } catch (\Throwable $e) {
                \Log::error('Failed to send maintenance completed email', [
                    'id' => $maintenanceRequest->id,
                    'branch_email' => $maintenanceRequest->technician_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        if ($status === config('sla_status.code.PENDING_CONTRACTOR')) {
            $data['pending_at'] = $now;
        }

        if ($status === config('sla_status.code.CONTINUE_PROCESSING')) {
            $data['processing_at'] = $now;
        }

        if ($status === config('sla_status.code.REOPEN')) {
            $data = array_merge($data, [
                'acceptance_result' => null,
                'acceptance_note' => null,
                'acceptance_confirmed_by' => null,
                'confirmed_at' => null,
                'is_confirmed' => false,
            ]);
        }
        // dd($data);

        DB::transaction(function () use (
            $maintenanceRequest,
            $data,
            $oldStatus,
            $status,
            $request
        ) {

            $maintenanceRequest->update($data);

            MaintenanceRequestLog::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'user_id'                => auth()->id(),
                'old_status'             => $oldStatus,
                'new_status'             => $status,
                'note'                   => $request->note,
            ]);

        });

        $maintenanceRequest->refresh();

        $this->afterStatusChanged(
            $maintenanceRequest,
            $status,
            $request
        );

        return response()->json([
            'success'    => true,
            'sla_status' => $maintenanceRequest->sla_status,
        ]);
    }

    private function afterStatusChanged(
        MaintenanceRequest $maintenanceRequest,
        string $requestedStatus,
        ChangeMaintenanceStatusRequest $request
    ): void {
        
        // Xử lý theo trạng thái thực tế sau update
        if ($requestedStatus === config('sla_status.code.WAITING_CONFIRM')) {
            $this->autoConfirm($maintenanceRequest);
        }
    
        // Upload ảnh
        $this->handleUploadImages(
            $maintenanceRequest,
            $requestedStatus,
            $request
        );
    
        // Gửi mail
        $this->handleSendMail(
            $maintenanceRequest,
            $requestedStatus
        );
    }

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
            \Log::error('Failed to queue acceptance email', [
                'id' => $item->id,
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
    
            \Log::error('Delete image failed', [
                'image_id' => $image->id,
                'error' => $e->getMessage(),
            ]);
    
            return response()->json([
                'success' => false,
                'message' => 'Xóa ảnh thất bại'
            ], 500);
        }
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
        $jsonPathNorth = resource_path('json/stores.json');
        $jsonPathSouth = resource_path('json/stores_mn.json');
        $jsonPathCiciNorth = resource_path('json/stores_cici_mb.json');
        $jsonPathCiciSouth = resource_path('json/stores_cici_mn.json');
        $storesNorth = json_decode(file_get_contents($jsonPathNorth), true);
        $storesSouth = json_decode(file_get_contents($jsonPathSouth), true);
        $storesCiciNorth = json_decode(file_get_contents($jsonPathCiciNorth), true);
        $storesCiciSouth = json_decode(file_get_contents($jsonPathCiciSouth), true);

        // Tạo cấu trúc rõ 2 miền
        $data = [
            'mien_bac' => $storesNorth,
            'mien_nam' => $storesSouth,
            'cici_mien_bac' => $storesCiciNorth,
            'cici_mien_nam' => $storesCiciSouth,
        ];

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

    private function determineSlaStatus(MaintenanceRequest $item): string
    {

        if (!$item->actual_duration) {
            return config('sla_status.code.LATED');
        }

        [$h, $i, $s] = explode(':', $item->actual_duration);

        $actualSeconds = ((int) $h * 3600) + ((int) $i * 60) + (int) $s;

        $realTimeList = config('real_time');
        $realTimeMap = collect($realTimeList)->keyBy('key');
        $stdKey = $item->standard_completion_time;

        if (!$stdKey || !isset($realTimeMap[$stdKey])) {
            return config('sla_status.code.LATED');
        }

        $maxSeconds = $realTimeMap[$stdKey]['max_seconds'];

        return $actualSeconds <= $maxSeconds
            ? config('sla_status.code.COMPLETED')
            : config('sla_status.code.LATED');
    }

    private function autoConfirm(MaintenanceRequest $maintenanceRequest): void
    {
        $maintenanceRequest->refresh();

        $newStatus = $this->determineSlaStatus($maintenanceRequest);

        DB::transaction(function () use ($maintenanceRequest, $newStatus) {

            $maintenanceRequest->update([
                'sla_status' => $newStatus,
            ]);

            MaintenanceRequestLog::create([
                'maintenance_request_id' => $maintenanceRequest->id,
                'user_id' => 1,
                'old_status' => config('sla_status.code.WAITING_CONFIRM'),
                'new_status' => $newStatus,
                'note' => 'Auto duyệt yêu cầu',
            ]);
        });

        $maintenanceRequest->refresh();
    }

    private function handleUploadImages(
        MaintenanceRequest $maintenanceRequest,
        string $requestedStatus,
        ChangeMaintenanceStatusRequest $request
    ): void {
    
        if (
            $requestedStatus !== config('sla_status.code.WAITING_CONFIRM')
            || !$request->hasFile('images')
        ) {
            return;
        }
    
        $tempFiles = [];
    
        foreach ($request->file('images') as $file) {
    
            $tempName = Str::uuid().'.'.$file->getClientOriginalExtension();
    
            $file->storeAs(
                'temp-maintenance',
                $tempName
            );
    
            $tempFiles[] = $tempName;
        }
    
        UploadMaintenanceImagesJob::dispatch(
            $maintenanceRequest->id,
            $tempFiles,
            $request->input('technician_mail')
                ?: auth()->user()->email
        )->afterCommit();
    }

    private function handleSendMail(
        MaintenanceRequest $maintenanceRequest,
        string $requestedStatus
    ): void {
    
        try {
    
            switch ($requestedStatus) {
    
                case config('sla_status.code.WAITING_CONFIRM'):
    
                    Mail::to($maintenanceRequest->branch_email)
                        ->queue(new MaintenanceCompletedMail($maintenanceRequest));
    
                    break;
    
                case config('sla_status.code.PENDING'):
    
                    Mail::to($maintenanceRequest->technician_email)
                        ->queue(new MaintenanceBuyerMail($maintenanceRequest));
    
                    break;
            }
    
        } catch (\Throwable $e) {
    
            \Log::error('Send mail failed', [
                'maintenance_request_id' => $maintenanceRequest->id,
                'status' => $requestedStatus,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
