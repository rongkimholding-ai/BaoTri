<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceSystemRequest;
use App\Http\Requests\UpdateMaintenanceSystemRequest;
use App\Mail\MaintenanceSystemAcceptanceMail;
use App\Mail\MaintenanceSystemBuyerMail;
use App\Mail\MaintenanceSystemCompletedMail;
use App\Mail\MaintenanceSystemReminderMail;
use App\Models\MaintenanceSystem;
use App\Models\MaintenanceSystemLog;
use App\Services\SlaCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MaintenanceSystemController extends Controller
{
    public function index(Request $request)
    {
        session(['current_module' => 'system']);
        $user = auth()->user();
        $role = $user->getRoleNames()->first();
        $email = strtolower($user->email);

        // Base query for data access control (copy logic from MaintenanceRequestController)
        $baseQuery = MaintenanceSystem::query();
        switch ($role) {
            case 'technician_system':
                $baseQuery->where(function ($query) use ($email) {
                    $query->where('technician_email', $email)
                          ->orWhere('created_by', $email);
                });
                break;
            case 'user':
                $baseQuery->where(function ($query) use ($email) {
                    $query->where('branch_email', $email)
                          ->orWhere('created_by', $email);
                });
                break;
            case 'manager':
            case 'am':
            case 'om':
            case 'viewer':
                if (!in_array($email, config('special_user.full_view'))) {
                    // lấy theo Store từ DB
                    $stores = \App\Models\Store::all()->toArray();
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

                    $baseQuery->where(function($q) use ($branchEmails, $email) {
                        if (!empty($branchEmails)) {
                            $q->whereIn('branch_email', $branchEmails);
                        } else {
                            $q->whereRaw('1=0');
                        }
                        // OR created_by current user
                        $q->orWhere('created_by', $email);
                    });
                }
                // else: allow all
                break;
            // admin, etc: unrestricted
        }

        // Filters
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
            'status'      => fn($q, $v) => $q->where('status', $v),
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

        // Helper to get status codes for tab logic
        $completedStatus = 'COMPLETED'; // or config if needed
        $latedStatus = 'LATED'; // or config if needed
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
        $processingRequests = tap($baseQueryClone(), function ($q) use ($completedStatus, $latedStatus, $newStatus) {
                $q->whereNotIn('status', [$completedStatus, $latedStatus, $newStatus]);
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
                $q->where('is_confirmed', true);
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
            ->whereNotIn('status', [$completedStatus, $latedStatus, $newStatus])
            ->count();

        $completedCount = $baseQueryClone()
            ->where('is_confirmed', true)
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
        $requestDate = now();

        // Theo migration: created_by, request_date, status thuộc trường; không có created_at custom
        $data['created_by'] = auth()->user()->email ?? null;
        $data['request_date'] = $requestDate;
        $data['status'] = 'NEW';
        // Nếu ngày request_date là thứ 7 hoặc Chủ Nhật (cuối tuần) thì bật các flag tương ứng
        $weekday = Carbon::parse($requestDate)->dayOfWeekIso; // 6: Thứ 7, 7: CN
        if ($weekday == 6) {
            $data['include_saturday'] = true;
        }
        if ($weekday == 7) {
            $data['include_sunday'] = true;
        }

        // Check if current time is outside working hours using BusinessTimeHelper
        if (!\App\Helpers\BusinessTimeHelper::isBusinessTime($requestDate)) {
            $data['is_off_worktime'] = true;
        }

        $maintenanceSystem = MaintenanceSystem::create($data);

        $maintenanceSystem->writeLog(
            id: $maintenanceSystem->id,
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
                // \Log::error('Failed to send maintenance reminder email', [
                //     'id' => $maintenanceSystem->id,
                //     'email' => $maintenanceSystem->technician_email,
                //     'error' => $e->getMessage(),
                // ]);
                \App\Services\LogService::error('queue',"Failed to queue system reminder email", [
                    'time' => microtime(true),
                    'request_id' => $maintenanceSystem->id,
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
            id: $maintenanceSystem->id,
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
                // $data['actual_completion_date'] = $completedAt;
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
               
                // Khi hoàn thành thì lý do trễ để rỗng
                $data['delay_reason'] = '';
                break;
            case 'PENDING':
            case 'PENDING_CONTRACTOR':
                $data['pending_at'] = $now;
                break;
            case 'CONTINUE_PROCESSING':
                $data['processing_at'] = $now;
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
            id: $maintenanceSystem->id,
            action: 'CHANGE_STATUS',
            oldStatus: $oldStatus,
            newStatus: $maintenanceSystem->status,
            note: $validated['note'] ?? null
        );
        
        $this->afterStatusChanged(
            $maintenanceSystem,
            $validated['status']
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
        if ($requestStatus == config('sla_status.code_ht.WAITING_CONFIRM')) {
            $this->autoConfirm($maintenanceSystem);
        }
    
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
                    // $data['actual_duration'] = $actualDuration;
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
            id: $maintenanceSystem->id,
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
                id: $item->id,
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
            \App\Services\LogService::error('queue',"Failed to queue system acceptance email", [
                    'time' => microtime(true),
                    'request_id' => $item->id,
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

    private function autoConfirm(MaintenanceSystem $maintenanceSystem): void
    {
        $maintenanceSystem->refresh();
        $slaService = app(SlaCalculatorService::class);
        $oldStatus = $maintenanceSystem->status;
        $newStatus = $slaService->determineSystemSlaStatus($maintenanceSystem);
        if ($maintenanceSystem->status === $newStatus) return;

        DB::transaction(function () use ($maintenanceSystem, $newStatus, $oldStatus) {
            $maintenanceSystem->update(['status' => $newStatus]);
           
            $maintenanceSystem->writeLog(
                id: $maintenanceSystem->id,
                action: 'AUTO_CONFIRM',
                oldStatus: $oldStatus,
                newStatus: $newStatus,
                note: 'Auto duyệt yêu cầu'
            );
        });
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
        $item = MaintenanceSystem::find($id);

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

    /**
     * Cập nhật trường actual_duration cho một MaintenanceSystem cụ thể.
     * Tính actual_duration dựa trên request_date và completed_at (hoặc ngày hiện tại nếu chưa có completed_at).
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateActualDuration($id)
    {
        $item = MaintenanceSystem::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bản ghi.'
            ], 404);
        }

        // Kiểm tra các trường cần thiết
        if (!$item->request_date) {
            return response()->json([
                'success' => false,
                'message' => 'Thiếu trường request_date.'
            ], 400);
        }

        // Nếu chưa có actual_completion_date thì lấy ngày hiện tại
        $end = $item->actual_completion_date ?? now();

        // Sử dụng service đã có để tính actual_duration (giả sử là chuỗi 'HH:MM:SS')
        $slaService = app(SlaCalculatorService::class);
        $actualDuration = $slaService->calculate($item, Carbon::parse($end));

        $item->actual_duration = $actualDuration;
        $item->save();

        return response()->json([
            'success' => true,
            'actual_duration' => $actualDuration
        ]);
    }

    public function setIncludeWeekendTrue($id)
    {
        $item = MaintenanceSystem::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bản ghi.'
            ], 404);
        }

        $item->include_saturday = true;
        $item->include_sunday = true;
        $item->save();

        return response()->json([
            'success' => true,
            'include_saturday' => $item->include_saturday,
            'include_sunday' => $item->include_sunday,
        ]);
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

                case config('sla_status.code_ht.PENDING'):
                    Mail::to($maintenanceRequest->technician_email)
                        ->queue(new MaintenanceSystemBuyerMail($maintenanceRequest));
                    break;
            }
    
        } catch (\Throwable $e) {
    
            // \Log::error('Send mail failed', [
            //     'maintenance_request_id' => $maintenanceRequest->id,
            //     'status' => $requestedStatus,
            //     'message' => $e->getMessage(),
            // ]);
            
            \App\Services\LogService::error('queue',"Failed to queue system send email", [
                    'time' => microtime(true),
                    'request_id' => $maintenanceRequest->id,
                    'status' => $requestedStatus,
                    'error' => $e->getMessage(),
                ]);
        }
    }
}
