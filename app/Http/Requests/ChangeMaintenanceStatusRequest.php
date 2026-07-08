<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeMaintenanceStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()
            ->can('change-maintenance-status');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'status' => [
                'required',
                'string',
                Rule::in(config('sla_status.code'))
            ],
            'technician_email' => ['string'],
        ];

        if (
            $this->status === config('sla_status.code.WAITING_CONFIRM')
        ) {
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

        return $rules;
    }
}
