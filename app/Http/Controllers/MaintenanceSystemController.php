<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceSystemRequest;
use App\Http\Requests\UpdateMaintenanceSystemRequest;
use App\Mail\MaintenanceSystemAcceptanceMail;
use App\Mail\MaintenanceSystemCompletedMail;
use App\Mail\MaintenanceSystemReminderMail;
use App\Models\MaintenanceSystem;
use App\Services\SlaCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MaintenanceSystemController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $role = $user->getRoleNames()->first();
        $email = strtolower($user->email);

        // Base query for data access control (copy logic from MaintenanceRequestController)
        $baseQuery = MaintenanceSystem::query();
        switch ($role) {
            case 'technician_system':
                $baseQuery->where('technician_email', $email);
                break;
            case 'user':
                $baseQuery->where('branch_email', $email);
                break;
            case 'manager':
            case 'am':
            case 'om':
            case 'viewer':
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
                    $branchEmails = collect($stores)
                        ->filter(function ($store) use ($email) {
                            return (
                                (isset($store['om_email']) && strtolower($store['om_email']) == $email) ||
                                (isset($store['am_email']) && strtolower($store['am_email']) == $email)
                            ) && !empty($store['email']);
                        })
                        ->pluck('email')
                        ->unique()
                        ->values()
                        ->all();

                    $baseQuery->when(!empty($branchEmails),
                        fn($q) => $q->whereIn('branch_email', $branchEmails),
                        fn($q) => $q->whereRaw('1=0')
                    );
                }
                // else: allow all
                break;
            // admin, etc: unrestricted
        }

        // Filters
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
            'id'          => fn($q, $v) => $q->where('id', $v),
        ];
        foreach ($filters as $field => $filter) {
            if ($request->filled($field)) {
                $filter($baseQuery, $request->$field);
            }
        }

        // Helper to get status codes for tab logic
        $completedStatus = 'COMPLETED'; // or config if needed
        $newStatus = 'NEW';

        $tab = $request->get('tab', 'all');
        $validTabs = ['all', 'processing', 'completed'];
        if (!in_array($tab, $validTabs)) {
            $tab = 'all';
        }

        // Only clone once per major query, like ref
        $baseQueryClone = fn() => clone $baseQuery;

        // allRequests tab (tất cả)
        $allRequests = $baseQueryClone()
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
            ->orderByDesc('request_date')
            ->paginate(20, ['*'], 'all_page')
            ->withQueryString();

        // processingRequests tab (đang xử lý)
        $processingRequests = tap($baseQueryClone(), function ($q) use ($completedStatus, $newStatus) {
                $q->whereNotIn('status', [$completedStatus, $newStatus]);
            })
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
            ->orderByDesc('request_date')
            ->paginate(20, ['*'], 'processing_page')
            ->withQueryString();

        // completedRequests tab (đã hoàn thành)
        $completedRequests = tap($baseQueryClone(), function ($q) {
                $q->where('status', 'COMPLETED');
            })
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
            ->orderByDesc('request_date')
            ->paginate(20, ['*'], 'completed_page')
            ->withQueryString();

        // Counts for tabs
        $totalCount = $baseQueryClone()->count();

        $processingCount = $baseQueryClone()
            ->whereNotIn('status', [$completedStatus, $newStatus])
            ->count();

        $completedCount = $baseQueryClone()
            ->where('status', 'COMPLETED')
            ->count();

        $stores = $this->getData();
        $techs  = method_exists($this, 'getTechnicianData') ? $this->getTechnicianData() : [];

        return view('system.index', compact(
            'allRequests',
            'processingRequests',
            'completedRequests',
            'stores',
            'techs',
            'totalCount',
            'processingCount',
            'completedCount',
            'tab'
        ));
    }

    public function create()
    {
        $data = [
            'stores'     => $this->getData(),
            'techs'      => $this->getTechnicianData(),
        ];
        return view('system.modals.create', $data);
    }

    public function store(StoreMaintenanceSystemRequest $request)
    {
        $data = $request->validated();

        // Theo migration: created_by, request_date, status thuộc trường; không có created_at custom
        $data['created_by'] = auth()->user()->email ?? null;
        $data['request_date'] = now();
        $data['status'] = 'NEW';

        $maintenanceSystem = MaintenanceSystem::create($data);

        $maintenanceSystem->writeLog(
            action: 'CREATE',
            newStatus: $maintenanceSystem->status,
            note: 'Khởi tạo yêu cầu'
        );

        // Tự động gửi mail nhắc việc cho kỹ thuật viên khi tạo mới
        if (!empty($maintenanceSystem->technician_email)) {
            try {
                $sendMail = $maintenanceSystem->technician_email;
                \Mail::to($sendMail)->queue(new MaintenanceSystemReminderMail($maintenanceSystem));
            } catch (\Throwable $e) {
                \Log::error('Failed to send maintenance reminder email', [
                    'id' => $maintenanceSystem->id,
                    'email' => $maintenanceSystem->technician_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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

        $title = 'Chi tiết bảo trì hạ tầng';
        return view(
            'system.show',
            compact('maintenanceSystem','title')
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

        // Theo migration: updated_by là trường tùy chỉnh; không có updated_at custom
        $data['updated_by'] = auth()->user()->email ?? null;

        // Nếu trạng thái chuyển sang COMPLETED mà chưa có completed_at mới thêm
        if (
            ($data['status'] ?? $maintenanceSystem->status) === 'COMPLETED'
            && is_null($maintenanceSystem->completed_at)
        ) {
            $data['completed_at'] = now();
            $data['completed_by'] = auth()->user()->email ?? null;
        }

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
        // dd($validated['status']);

        if (!in_array(
            $validated['status'],
            $workflow[$maintenanceSystem->status] ?? []
        )) {
            abort(403);
        }

        $data = [
            'status' => $validated['status'],
            // Theo migration, delay_reason lưu lý do, không phải note
            'delay_reason' => $validated['delay_reason'] ?? null,
            'updated_by' => auth()->user()->email ?? null,
        ];

        // Logic tính toán thời gian, xử lý tương tự Request controller
        $now = now();

        switch ($validated['status']) {
            case config('sla_status.code_ht.WAITING_CONFIRM'):
                $completedAt = $now;
                $data['actual_completion_date'] = $completedAt;
                $data['delay_reason'] = '';

                $data['actual_duration'] = app(SlaCalculatorService::class)
                ->calculate(
                    $maintenanceSystem,
                    $completedAt
                );
                $this->afterStatusChanged(
                    $maintenanceSystem,
                    $validated['status']
                );
                break;
            case 'COMPLETED':
                if (!$maintenanceSystem->completed_at) {
                    $data['completed_at'] = $now;
                    $data['completed_by'] = auth()->user()->email ?? null;
                }
               
                // Khi hoàn thành thì lý do trễ để rỗng
                $data['delay_reason'] = '';
                break;
            case 'REOPEN':
                // Reset thông tin xác nhận khi reopen, tương tự request controller
                $data = array_merge($data, [
                    'acceptance_result' => null,
                    'acceptance_note' => null,
                    'acceptance_confirmed_by' => null,
                    'completed_at' => null,
                    'completed_by' => null,
                ]);
                break;
            // Add cases if needed, ví dụ các trạng thái khác
            default:
                // No extra logic
                break;
        }

        $maintenanceSystem->update($data);

        $maintenanceSystem->writeLog(
            action: 'CHANGE_STATUS',
            oldStatus: $oldStatus,
            newStatus: $maintenanceSystem->status,
            note: $validated['note'] ?? null
        );

        return response()->json([
            'success'    => true,
            'status' => $maintenanceSystem->status,
        ]);
    }

    private function afterStatusChanged(
        MaintenanceSystem $maintenanceSystem,
        string $requestStatus,
    ): void {
    
        // Gửi mail
        $this->handleSendMail(
            $maintenanceSystem,
            $requestStatus
        );
    }

    /**
     * Admin can change status to any value (without workflow constraint).
     */
    public function changeStatusAdmin(
        Request $request,
        MaintenanceSystem $maintenanceSystem
    )
    {
        $oldStatus = $maintenanceSystem->status;

        $validated = $request->validate([
            'status' => ['required'],
            'delay_reason' => ['nullable', 'string'],
            'note' => ['nullable', 'string']
        ]);
        // Không kiểm tra workflow, admin được phép set sang bất kỳ trạng thái nào

        $data = [
            'status' => $validated['status'],
            'delay_reason' => $validated['delay_reason'] ?? null,
            'updated_by' => auth()->user()->email ?? null,
        ];

        $now = now();

        switch ($validated['status']) {
            case config('sla_status.code.WAITING_CONFIRM'):
                $completedAt = $now;
                $data['actual_completion_date'] = $completedAt;
                $data['delay_reason'] = '';

                $data['actual_duration'] = app(SlaCalculatorService::class)
                ->calculate(
                    $maintenanceSystem,
                    $completedAt
                );
                break;
            case 'COMPLETED':
                if (!$maintenanceSystem->completed_at) {
                    $data['completed_at'] = $now;
                    $data['completed_by'] = auth()->user()->email ?? null;
                }
                if ($maintenanceSystem->request_date && (isset($data['completed_at']) || $maintenanceSystem->completed_at)) {
                    $start = $maintenanceSystem->request_date;
                    $end = $data['completed_at'] ?? $maintenanceSystem->completed_at;
                    $actualDuration = ceil((strtotime($end) - strtotime($start)) / 60);
                    $data['actual_duration'] = $actualDuration;
                }
                $data['delay_reason'] = '';
                break;
            case 'REOPEN':
                $data = array_merge($data, [
                    'acceptance_result' => null,
                    'acceptance_note' => null,
                    'acceptance_confirmed_by' => null,
                    'completed_at' => null,
                    'completed_by' => null,
                ]);
                break;
            default:
                break;
        }

        $maintenanceSystem->update($data);

        $maintenanceSystem->writeLog(
            action: 'CHANGE_STATUS_ADMIN',
            oldStatus: $oldStatus,
            newStatus: $maintenanceSystem->status,
            note: $validated['note'] ?? null
        );

        return response()->json([
            'success'    => true,
            'status' => $maintenanceSystem->status,
        ]);
    }

    public function acceptance(Request $request)
    {
        // dd($request->request);
        // abort_unless(auth()->user()->can('confirm maintenance'), 403);

        $request->validate([
            'id'     => 'required',
            'result' => 'required|in:accepted,rejected',
            'note'   => 'nullable|string|max:1000',
        ]);

        $item = MaintenanceSystem::findOrFail($request->id);

        if (!in_array($item->status, [
            config('sla_status.code_ht.COMPLETED'),
            config('sla_status.code_ht.LATED')
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

            $item->save();

            $item->writeLog(
                action: 'ACCEPTANCE',
                oldStatus: $item->status,
                newStatus: $item->status,
                note: $request->note ?: ($request->result === 'accepted'
                                            ? 'Nghiệm thu đạt' : 'Nghiệm thu không đạt'),
            );
        });

        // refresh để lấy data mới
        $item->refresh();

        /*
        |--------------------------------------------------------------------------
        | 2. SEND MAIL → QUEUE (KHÔNG send sync)
        |--------------------------------------------------------------------------
        */
        try {
            Mail::to($item->technician_email)
                ->queue(new MaintenanceSystemAcceptanceMail($item));

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

    public function getTechnicianData()
    {
        $data = config('technician_ht');
        return $data;
    }
    private function handleSendMail(
        MaintenanceSystem $maintenanceRequest,
        string $requestedStatus
    ): void {
    
        try {
    
            switch ($requestedStatus) {
    
                case config('sla_status.code_ht.WAITING_CONFIRM'):
    
                    Mail::to($maintenanceRequest->branch_email)
                        ->queue(new MaintenanceSystemCompletedMail($maintenanceRequest));
    
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
