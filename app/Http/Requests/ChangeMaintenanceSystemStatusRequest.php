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
        return is_numeric($system) ? MaintenanceSystem::find((int)$system) : null;
    }

    public function rules(): array
    {
        $codes = config('sla_status.code_ht', []);
        $waiting = $codes['WAITING_CONFIRM'] ?? null;
        $offlineType = config('work_type.code.OFFLINE');

        $rules = [
            'status'           => ['required', 'string', Rule::in($codes)],
            'technician_email' => ['nullable', 'string'],
            'latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180'],
            'accuracy'         => ['nullable', 'numeric', 'min:0', 'max:300'],
            'work_type'        => ['nullable'],
        ];

        if ($this->input('status') == $waiting) {
            $rules['images']    = ['required', 'array', 'min:1', 'max:10'];
            $rules['images.*']  = ['image', 'mimes:jpg,jpeg,png,webp', 'max:10240'];
        }

        $type = $this->system()?->work_type ?? $this->input('work_type');

        if ($this->input('status') == $waiting && (int)$type === (int)$offlineType) {
            $rules['latitude'][]  = 'required';
            $rules['longitude'][] = 'required';
            $rules['accuracy'][]  = 'required';
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) return;

                $codes = config('sla_status.code_ht', []);
                $waiting = $codes['WAITING_CONFIRM'] ?? null;
                $offlineType = config('work_type.code.OFFLINE');

                if ($this->input('status') != $waiting) return;

                $system = $this->system();
                if (!$system) {
                    $validator->errors()->add('latitude', 'Không xác định được bản ghi bảo trì để kiểm tra vị trí.');
                    return;
                }
                if ((int) $system->work_type !== (int) $offlineType) return;

                $lat = $this->input('latitude');
                $lng = $this->input('longitude');
                $accuracy = $this->input('accuracy');

                if (!is_numeric($lat) || !is_numeric($lng) || !is_numeric($accuracy)) {
                    $validator->errors()->add(
                        'latitude',
                        'Không nhận được đầy đủ thông tin GPS hợp lệ. Vui lòng bật định vị và thử lại.'
                    );
                    return;
                }

                $store = $system->store
                    ?? (filled($system->branch_email) ? Store::where('email', trim($system->branch_email))->first() : null)
                    ?? (filled($system->branch_name) ? Store::where('name', trim($system->branch_name))->first() : null);

                if (!$store || !is_numeric($store->latitude) || !is_numeric($store->longitude)) {
                    $validator->errors()->add(
                        'latitude',
                        'Không tìm thấy cửa hàng tương ứng hoặc cửa hàng chưa có tọa độ GPS hợp lệ. Vui lòng kiểm tra mã, email, tên và tọa độ cửa hàng.'
                    );
                    return;
                }

                $distance = $this->distance(
                    (float) $store->latitude,
                    (float) $store->longitude,
                    (float) $lat,
                    (float) $lng
                );

                if ($distance > 300) {
                    $validator->errors()->add(
                        'latitude',
                        'Bạn đang ở ngoài phạm vi 300 mét của cửa hàng. ' .
                        'Khoảng cách GPS hiện tại khoảng ' . round($distance) .
                        ' mét. Vui lòng di chuyển đến gần cửa hàng để xác nhận.'
                    );
                }
            }
        ];
    }

    // Haversine: trả về mét
    private function distance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $rad = fn($deg) => deg2rad($deg);
        $lat1 = $rad($lat1); $lon1 = $rad($lon1);
        $lat2 = $rad($lat2); $lon2 = $rad($lon2);

        $dLat = $lat2 - $lat1; $dLon = $lon2 - $lon1;
        $a = sin($dLat/2)**2 + cos($lat1)*cos($lat2)*sin($dLon/2)**2;
        $a = min(1, max(0, $a));
        return 6371000 * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public function messages(): array
    {
        return [
            'status.required'     => 'Vui lòng chọn trạng thái.',
            'status.in'           => 'Trạng thái không hợp lệ.',
            'images.required'     => 'Vui lòng tải lên ít nhất một hình ảnh.',
            'images.array'        => 'Danh sách hình ảnh không hợp lệ.',
            'images.min'          => 'Vui lòng tải lên ít nhất một hình ảnh.',
            'images.max'          => 'Chỉ được tải lên tối đa 10 hình ảnh.',
            'images.*.image'      => 'Tệp tải lên phải là hình ảnh.',
            'images.*.mimes'      => 'Hình ảnh chỉ chấp nhận định dạng JPG, JPEG, PNG hoặc WEBP.',
            'images.*.max'        => 'Mỗi hình ảnh không được vượt quá 10 MB.',
            'latitude.required'   => 'Vui lòng cung cấp vị trí GPS.',
            'latitude.numeric'    => 'Vĩ độ GPS không hợp lệ.',
            'latitude.between'    => 'Vĩ độ GPS nằm ngoài phạm vi hợp lệ.',
            'longitude.required'  => 'Vui lòng cung cấp vị trí GPS.',
            'longitude.numeric'   => 'Kinh độ GPS không hợp lệ.',
            'longitude.between'   => 'Kinh độ GPS nằm ngoài phạm vi hợp lệ.',
            'accuracy.required'   => 'Vui lòng cung cấp độ chính xác GPS.',
            'accuracy.numeric'    => 'Thông tin độ chính xác GPS không hợp lệ.',
            'accuracy.min'        => 'Độ chính xác GPS không hợp lệ.',
            'accuracy.max'        => 'Độ chính xác GPS không được vượt quá 300 mét. Vui lòng bật định vị chính xác và thử lại.',
        ];
    }
}
