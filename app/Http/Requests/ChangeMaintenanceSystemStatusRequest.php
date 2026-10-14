<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeMaintenanceSystemStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->can('change-system-status') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $statusCodes = config('sla_status.code_ht');
        $waitingConfirm = $statusCodes['WAITING_CONFIRM'] ?? null;
        $offlineWorkType = config('work_type.code.OFFLINE');

        $rules = [
            'status' => [
                'required',
                'string',
                Rule::in($statusCodes),
            ],
            'technician_email' => ['nullable', 'string'],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            'accuracy' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'work_type' => [
                'nullable',
            ]
        ];

        if ($this->status == $waitingConfirm) {
            $rules['images'] = [
                'required',
                'array',
                'min:1',
                'max:10'
            ];
            $rules['images.*'] = [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240'
            ];
        }

        if (
            (int) $this->work_type === (int) $offlineWorkType &&
            $this->status == $waitingConfirm
        ) {
            $rules['latitude'][] = 'required';
            $rules['longitude'][] = 'required';
            $rules['accuracy'][] = 'required';
        }

        return $rules;
    }
}
