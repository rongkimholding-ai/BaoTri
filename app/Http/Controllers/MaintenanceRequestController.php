<?php

namespace App\Http\Controllers;

use App\Helpers\BusinessTimeHelper;
use App\Http\Requests\StoreMaintenanceRequest;
use App\Mail\MaintenanceCompletedMail;
use App\Models\MaintenanceRequest;
use App\Models\MaintenanceRequestLog;
use App\Services\MaintenanceRequestService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\MaintenanceReminderMail;
use Intervention\Image\Drivers\Gd\Encoders\JpegEncoder;
use Storage;
use App\Models\MaintenanceRequestImage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class MaintenanceRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $baseQuery = MaintenanceRequest::query();

        // Phân quyền dữ liệu
        // Nếu là technician, chỉ nhìn thấy những request có technician_email == user email
        if ($user->hasRole('technician')) {
            $baseQuery->where('technician_email', $user->email);
        }
        // Nếu là user, chỉ nhìn thấy những request có branch_email == user email
        else if ($user->hasRole('user')) {
            $baseQuery->where('branch_email', $user->email);
        }
        // Nếu là admin, không giới hạn

        $filters = [
            'from_date' => function ($q, $v) {
                if (!empty($v)) {
                    $startOfDay = Carbon::parse($v)->startOfDay();
                    $q->where('request_date', '>=', $startOfDay);
                }
            },
            'to_date' => function ($q, $v) {
                if (!empty($v)) {
                    $endOfDay = Carbon::parse($v)->endOfDay();
                    $q->whereDate('request_date', '<=', $endOfDay);
                }
            },
            'branch_code' => fn($q, $v) => $q->where('branch_code', 'like', "%$v%"),
            'branch_name' => fn($q, $v) => $q->where('branch_name', 'like', "%$v%"),
            'severity'    => fn($q, $v) => $q->where('severity', $v),
            'status'      => fn($q, $v) => $q->where('sla_status', $v),
            'id'          => fn($q, $v) => $q->where('id', $v),
        ];

        foreach ($filters as $field => $closure) {
            if ($request->filled($field)) {
                $closure($baseQuery, $request->$field);
            }
        }

        $completedStatus = config('sla_status.code.COMPLETED');
        $newStatus = config('sla_status.code.NEW');

        // Thứ tự severity mong muốn từ config
        $severityOrder = array_map(function ($item) {
            return $item['key'];
        }, config('severities'));

        // Tạo chuỗi cho FIELD() mysql
        $severityOrderStr = implode("','", $severityOrder);

        // Định nghĩa một hàm order theo thứ tự severity
        $ordered = function ($query) use ($severityOrderStr) {
            return $query->orderByRaw("FIELD(severity, '$severityOrderStr')")->orderByDesc('id');
        };

        // Pagination queries
        $allRequests = $ordered(clone $baseQuery)->paginate(20, ['*'], 'all_page')->withQueryString();

        $processingRequests = $ordered(
            (clone $baseQuery)
                ->where('sla_status', '!=', $completedStatus)
                ->where('sla_status', '!=', $newStatus)
                ->where('is_confirmed', '!=', true)
        )->paginate(20, ['*'], 'processing_page')->withQueryString();

        $completedRequests = $ordered(
            (clone $baseQuery)->where('is_confirmed', true)
        )->paginate(20, ['*'], 'completed_page')->withQueryString();

        // Counts
        $totalCount = (clone $baseQuery)->count();

        $processingCount = (clone $baseQuery)
            ->whereNotNull('technician_name')
            ->where('sla_status', '!=', $completedStatus)
            ->where('sla_status', '!=', $newStatus)
            ->where('is_confirmed', '!=', true)->count();

        $completedCount = (clone $baseQuery)->where('is_confirmed', true)->count();

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
                \Mail::to($sendMail)->send(new MaintenanceReminderMail($created));
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
        $item = MaintenanceRequest::findOrFail($request->id);

        if (empty($item->technician_email)) {
            return response()->json([
                'success' => false,
                'message' => 'Chưa khai báo email kỹ thuật viên'
            ], 422);
        }

        try {
            $email = $item->technician_email;
            // $email = env('MAIL_NOTIFICATION_CC');
            Mail::to($email)->send(new MaintenanceReminderMail($item));
            $item->increment('reminder_count', 1, ['last_reminded_at' => now()]);

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
                $actualSeconds = ((int) $h) * 3600 + ((int) $i) * 60 + ((int) $s);
            }

            // Lấy danh sách thời gian chuẩn từ config
            $realTimeList = config('real_time');
            $realTimeMap = collect($realTimeList)->keyBy('key');

            $stdKey = $item->standard_completion_time;
            $minSeconds = null;
            $maxSeconds = null;
            if ($stdKey && isset($realTimeMap[$stdKey])) {
                $minSeconds = $realTimeMap[$stdKey]['min_seconds'];
                $maxSeconds = $realTimeMap[$stdKey]['max_seconds'];
            }

            // So sánh actualSeconds với max (nếu tồn tại)
            if (!is_null($maxSeconds) && $actualSeconds > 0) {
                if ($actualSeconds > $maxSeconds) {
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
            $item->acceptance_result = 'accepted';
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
            'status_name' => config('sla_status.names.COMPLETED'),
            'badge_class' => config('sla_status.badge.COMPLETED'),
        ]);
    }

    public function changeStatus(Request $request, MaintenanceRequest $maintenanceRequest)
    {
        $allowedStatuses = config('sla_status.code');

        $request->validate([
            'status' => ['string', 'in:' . implode(',', $allowedStatuses)],
        ]);

        if ($request->status === config('sla_status.code.WAITING_CONFIRM')) {
            $request->validate([
                'images' => ['required', 'array', 'min:1'],
                'images.*' => [
                    'image',
                    'mimes:jpg,jpeg,png,webp',
                    'max:10240',
                ],
            ]);
        }

        // Kiểm tra quyền
        abort_unless(auth()->user()->can('change-maintenance-status'), 403);

        $oldStatus = $maintenanceRequest->sla_status;
        $status    = $request->status;
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
                // if ($maintenanceRequest->pending_at && $maintenanceRequest->processing_at) {
                //     $beforePendingSeconds =
                //         BusinessTimeHelper::diffInBusinessSeconds(
                //             $maintenanceRequest->request_date,
                //             $maintenanceRequest->pending_at,
                //             $maintenanceRequest->include_saturday,
                //             $maintenanceRequest->include_sunday,
                //             $maintenanceRequest->include_holiday
                //         );
                //     $afterResumeSeconds =
                //         BusinessTimeHelper::diffInBusinessSeconds(
                //             $maintenanceRequest->processing_at,
                //             $completedAt,
                //             $maintenanceRequest->include_saturday,
                //             $maintenanceRequest->include_sunday,
                //             $maintenanceRequest->include_holiday
                //         );
                //     $totalSeconds = $beforePendingSeconds + $afterResumeSeconds;
                // } else {
                $totalSeconds =
                    BusinessTimeHelper::diffInBusinessSeconds(
                        $maintenanceRequest->request_date,
                        $completedAt,
                        $maintenanceRequest->include_saturday,
                        $maintenanceRequest->include_sunday,
                        $maintenanceRequest->include_holiday
                    );
                // }
                $data['actual_duration'] =
                    BusinessTimeHelper::formatDuration(
                        $totalSeconds
                    );

                if (auth()->user()->email === 'baotri@tocotocotea.com') {
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

                // Gửi mail thông báo khi hoàn thành công việc
                try {
                    Mail::to($maintenanceRequest->branch_email)
                        ->send(new MaintenanceCompletedMail($maintenanceRequest));
                } catch (\Throwable $e) {
                    \Log::error('Failed to send maintenance completed email', [
                        'id' => $maintenanceRequest->id,
                        'branch_email' => $maintenanceRequest->branch_email,
                        'error' => $e->getMessage(),
                    ]);
                }
   
                break;

            case config('sla_status.code.CONFIRMED'):
                $data['sla_status'] = $this->determineSlaStatus($maintenanceRequest);
                break;
        }

        if (in_array($status, [config('sla_status.code.PENDING'), config('sla_status.code.PENDING_CONTRACTOR')])) {
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

        $maintenanceRequest->update($data);

        if (
            $status === config('sla_status.code.WAITING_CONFIRM')
            && $request->hasFile('images')
        ) {
            $manager = new ImageManager(new Driver());
            foreach ($request->file('images') as $file) {
                $image = $manager->read($file);
                $image->scaleDown(
                    width: 1280,
                    height: 1280
                );
                $fileName = uniqid() . '.jpg';
                $dateFolder = now()->format('Y_m_d');
                $path = 'images/' . $dateFolder . '/' . $fileName;
                $encoded = $image->encode(
                    new JpegEncoder(quality: 70)
                );
                Storage::disk('public')->put(
                    $path,
                    $encoded
                );

                MaintenanceRequestImage::create([
                    'maintenance_request_id' => $maintenanceRequest->id,
                    'path' => $path,
                    'uploaded_by' => $request->input('tech_mail') ?: auth()->user()->email,
                ]);
            }
        }

        MaintenanceRequestLog::create([
            'maintenance_request_id' => $maintenanceRequest->id,
            'user_id'                => auth()->id(),
            'old_status'             => $oldStatus,
            'new_status'             => $status,
            'note'                   => $request->note,
        ]);

        return response()->json([
            'success'    => true,
            'sla_status' => $maintenanceRequest->status,
        ]);
    }

    public function acceptance(Request $request)
    {
        abort_unless(auth()->user()->can('confirm maintenance'), 403);

        $request->validate([
            'id'     => 'required',
            'result' => 'required|in:accepted,rejected',
            'note'   => 'nullable|string|max:1000'
        ]);

        $item = MaintenanceRequest::findOrFail($request->id);

        if (!in_array($item->sla_status,[
                config('sla_status.code.COMPLETED'),
                config('sla_status.code.LATED')
            ])
        ) {
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
                // Lấy danh sách thời gian chuẩn từ config
                $realTimeList   = config('real_time');
                $realTimeMap    = collect($realTimeList)->keyBy('key');
                $sla            = $realTimeMap[$item->standard_completion_time] ?? null;
                $createdAt      = Carbon::parse($item->request_date);
                $elapsedSeconds = $createdAt->diffInSeconds(now());
                $isOverdue      = $elapsedSeconds > (int) $sla['max_seconds'];
                if ($isOverdue) {
                    $item->is_confirmed            = ($request->result === 'rejected');
                    $item->sla_status              = config('sla_status.code.LATED');
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
                'note'                   => $request->result === 'accepted' 
                                            ? 'Nghiệm thu đạt' 
                                            : 'Nghiệm thu không đạt: ' . $request->note,
            ]);
        });

        return response()->json([
            'success' => true
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
}
