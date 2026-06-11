<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() &&
        auth()->user()->can('create data');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_name' => 'required|string|max:255',
            'branch_code' => 'required|string|max:255',
            'item_category' => 'required|string|max:255',
            'issue_description' => 'required|string|max:255',
            'severity' => 'required|string|max:255',
            // 'issue_category' => 'nullable|string|max:255',
            'technician_name' => 'required|string|max:255',
            'technician_mobile' => 'nullable|string|max:255',
            'technician_email' => 'nullable|email',
            'standard_completion_time' => 'required|string',
            'solution_description' => 'required|string|max:255',
            'request_date' => 'nullable|date_format:Y-m-d H:i:s',
            'actual_completion_date' => 'nullable|date_format:Y-m-d H:i:s',
            'actual_duration' => 'nullable|string|max:255',
            'sla_status' => 'nullable|string|max:255',
            'delay_reason' => 'nullable|string|max:255',
            'outsourced_provider' => 'nullable|string|max:255',
            'acceptance_result' => 'nullable|string|max:255',
            'acceptance_confirmed_by' => 'nullable|string|max:255',
        ];
    }

    protected function prepareForValidation()
    {
        \Log::info('prepareForValidation', $this->all());
    }
}
