<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMaintenanceSystemRequest;
use App\Http\Requests\UpdateMaintenanceSystemRequest;
use App\Models\MaintenanceSystem;
use App\Models\MaintenanceSystemLog;
use App\Services\SlaCalculatorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                // Tính actual_duration nếu có ngày bắt đầu/kết thúc
                // (ví dụ: dựa trên request_date/completed_at như MaintenanceRequest)
                if ($maintenanceSystem->request_date && (isset($data['completed_at']) || $maintenanceSystem->completed_at)) {
                    $start = $maintenanceSystem->request_date;
                    $end = $data['completed_at'] ?? $maintenanceSystem->completed_at;
                    // Bạn có thể thay bằng Service hoặc helper nếu cần, đơn giản hóa ở đây (phút)
                    $actualDuration = ceil((strtotime($end) - strtotime($start)) / 60);
                    $data['actual_duration'] = $actualDuration;
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

        return redirect()
            ->route('maintenance-system.index')
            ->with('success', 'Đổi trạng thái thành công.');
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

        return redirect()
            ->route('maintenance-system.index')
            ->with('success', 'Admin đã đổi trạng thái thành công.');
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
        $jsonPathCiciSouth = resource_path('json/stores_cici_mn.json');
        $storesNorth = json_decode(file_get_contents($jsonPathNorth), true);
        $storesSouth = json_decode(file_get_contents($jsonPathSouth), true);
        $storesCiciSouth = json_decode(file_get_contents($jsonPathCiciSouth), true);

        // Tạo cấu trúc rõ 2 miền
        $data = [
            'mien_bac' => $storesNorth,
            'mien_nam' => $storesSouth,
            'cici_mien_nam' => $storesCiciSouth,
        ];

        return $data;
    }

    public function getTechnicianData()
    {
        $data = config('technician_ht');
        return $data;
    }
}
