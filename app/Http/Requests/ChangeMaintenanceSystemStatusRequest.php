<?php

namespace App\Http\Requests;

use App\Models\MaintenanceSystem;
use App\Models\Store;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ChangeMaintenanceSystemStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()?->can('change-system-status') ?? false;
    }

    private function system(): ?MaintenanceSystem
    {
        $system = $this->route('maintenanceSystem') ?? $this->route('maintenance_system');
        if ($system instanceof MaintenanceSystem) return $system;
        return is_numeric($system) ? MaintenanceSystem::find((int) $system) : null;
    }

    /**
     * Xác định loại OFFLINE cho lần gửi hiện tại, KHÔNG lấy từ DB.
     */
    private function isOfflineRequest(): bool
    {
        $value = $this->input('work_type');
        $offlineType = config('work_type.code.OFFLINE');
        if ($value === null || $value === '') return false;

        if (is_numeric($value) && is_numeric($offlineType)) return (int)$value === (int)$offlineType;

        $value = strtoupper(trim((string)$value));
        $offlineText = strtoupper(trim((string)$offlineType));

        if (in_array($value, ['OFFLINE', 'OFF_LINE'], true)) return true;

        return $value !== '' && $offlineText !== '' && $value === $offlineText;
    }

    public function rules(): array
    {
        $statusCodes = config('sla_status.code_ht', []);
        $waitingConfirm = $statusCodes['WAITING_CONFIRM'] ?? null;
        $workTypeCodes = config('work_type.code', []);
        $rules = [
            'status' => ['required', 'string', Rule::in($statusCodes)],
            'technician_email' => ['nullable', 'string'],
            'work_type' => ['nullable'],
        ];

        if ($this->input('status') == $waitingConfirm) {
            $rules['images'] = ['required', 'array', 'min:1', 'max:10'];
            $rules['images.*'] = ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'];
            $allowedWorkTypes = array_map('strval', array_values($workTypeCodes));
            $rules['work_type'] = ['required', Rule::in($allowedWorkTypes)];
        }

        if ($this->input('status') == $waitingConfirm && $this->isOfflineRequest()) {
            $rules['latitude'] = ['required', 'numeric', 'between:-90,90'];
            $rules['longitude'] = ['required', 'numeric', 'between:-180,180'];
            $rules['accuracy'] = ['required', 'numeric', 'min:0', 'max:300'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) return;

                $statusCodes = config('sla_status.code_ht', []);
                $waitingConfirm = $statusCodes['WAITING_CONFIRM'] ?? null;

                if ($this->input('status') != $waitingConfirm || !$this->isOfflineRequest()) return;

                $system = $this->system();
                if (!$system) {
                    $validator->errors()->add('status', 'Không xác định được bản ghi bảo trì để kiểm tra vị trí. Vui lòng tải lại trang và thử lại.');
                    return;
                }

                $latitude = $this->input('latitude');
                $longitude = $this->input('longitude');
                $accuracy = $this->input('accuracy');
                if (!is_numeric($latitude) || !is_numeric($longitude) || !is_numeric($accuracy)) {
                    $validator->errors()->add('latitude', 'Không nhận được đầy đủ thông tin GPS hợp lệ. Vui lòng bật định vị và thử lại.');
                    return;
                }

                // Tìm cửa hàng
                $store = $system->store;
                if (!$store && filled($system->branch_email)) {
                    $store = Store::where('email', trim($system->branch_email))->first();
                }
                if (!$store && filled($system->branch_name)) {
                    $store = Store::where('name', trim($system->branch_name))->first();
                }

                if (!$store || !is_numeric($store->latitude) || !is_numeric($store->longitude)) {
                    $validator->errors()->add('latitude', 'Không tìm thấy cửa hàng tương ứng hoặc cửa hàng chưa có tọa độ GPS hợp lệ. Vui lòng kiểm tra thông tin cửa hàng.');
                    return;
                }

                if ((float)$accuracy > 300) {
                    $validator->errors()->add('accuracy', 'Độ chính xác GPS không được vượt quá 300 mét. Vui lòng bật định vị chính xác và thử lại.');
                    return;
                }

                $distance = $this->distance(
                    (float)$store->latitude,
                    (float)$store->longitude,
                    (float)$latitude,
                    (float)$longitude
                );
                if ($distance > 300) {
                    $validator->errors()->add(
                        'latitude',
                        'Bạn đang ở ngoài phạm vi 300 mét của cửa hàng. Khoảng cách hiện tại khoảng '.round($distance).' mét. Vui lòng di chuyển đến gần cửa hàng để xác nhận.'
                    );
                }
            }
        ];
    }

    /**
     * Tính khoảng cách hai tọa độ (mét) theo Haversine.
     */
    private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        [$lat1, $lon1, $lat2, $lon2] = array_map('deg2rad', [$lat1, $lon1, $lat2, $lon2]);
        $a = sin(($lat2 - $lat1)/2) ** 2 + cos($lat1) * cos($lat2) * sin(($lon2 - $lon1)/2) ** 2;
        $a = max(0.0, min(1.0, $a));
        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function messages(): array
    {
        return [
            'status.required'    => 'Vui lòng chọn trạng thái.',
            'status.in'          => 'Trạng thái không hợp lệ.',
            'work_type.required' => 'Vui lòng chọn loại công việc Online hoặc Offline.',
            'work_type.in'       => 'Loại công việc không hợp lệ. Vui lòng chọn lại.',
            'images.required'    => 'Vui lòng tải lên ít nhất một hình ảnh.',
            'images.array'       => 'Danh sách hình ảnh không hợp lệ.',
            'images.min'         => 'Vui lòng tải lên ít nhất một hình ảnh.',
            'images.max'         => 'Chỉ được tải lên tối đa 10 hình ảnh.',
            'images.*.image'     => 'Tệp tải lên phải là hình ảnh.',
            'images.*.mimes'     => 'Hình ảnh chỉ chấp nhận định dạng JPG, JPEG, PNG hoặc WEBP.',
            'images.*.max'       => 'Mỗi hình ảnh không được vượt quá 10 MB.',
            'latitude.required'  => 'Vui lòng cung cấp vị trí GPS.',
            'latitude.numeric'   => 'Vĩ độ GPS không hợp lệ.',
            'latitude.between'   => 'Vĩ độ GPS nằm ngoài phạm vi hợp lệ.',
            'longitude.required' => 'Vui lòng cung cấp vị trí GPS.',
            'longitude.numeric'  => 'Kinh độ GPS không hợp lệ.',
            'longitude.between'  => 'Kinh độ GPS nằm ngoài phạm vi hợp lệ.',
            'accuracy.required'  => 'Vui lòng cung cấp độ chính xác GPS.',
            'accuracy.numeric'   => 'Thông tin độ chính xác GPS không hợp lệ.',
            'accuracy.min'       => 'Độ chính xác GPS không hợp lệ.',
            'accuracy.max'       => 'Độ chính xác GPS không được vượt quá 300 mét. Vui lòng bật định vị chính xác và thử lại.',
        ];
    }
}
