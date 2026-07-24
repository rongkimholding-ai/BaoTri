<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMaintenanceSystemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Thông tin sự cố / Dịch vụ
            'issue_code' => ['nullable', 'string', 'max:50'],
            'issue_name' => ['required', 'string', 'max:255'],
            'issue_description' => ['nullable', 'string'],
            'solution_description' => ['nullable', 'string'],

            // Chi nhánh
            'branch_code' => ['nullable', 'string', 'max:20'],
            'branch_name' => ['required', 'string', 'max:255'],
            'branch_email' => ['nullable', 'email', 'max:255'],

            // Kỹ thuật viên xử lý
            'technician_name' => ['nullable', 'string', 'max:255'],
            'technician_email' => ['nullable', 'email', 'max:255'],
            'technician_mobile' => ['nullable', 'string', 'max:30'],

            // Ngày yêu cầu
            // request_date sẽ được set mặc định ở Controller
            'actual_completion_date' => ['nullable', 'string', 'max:30'],
            'completed_at' => ['nullable', 'date'],
            'standard_completion_time' => ['nullable', 'string', 'max:191'],
            'include_saturday' => ['nullable', 'boolean'],
            'include_sunday' => ['nullable', 'boolean'],
            'include_holiday' => ['nullable', 'boolean'],
            'is_off_worktime' => ['nullable', 'boolean'],
            'actual_duration' => ['nullable', 'string', 'max:191'],
            'sla_status' => ['nullable', 'string', 'max:191'],

            // Status, lý do trễ, nhà thầu ngoài
            'status' => ['nullable', 'string', 'max:30'],
            'delay_reason' => ['nullable', 'string'],

            // Nghiệm thu
            'acceptance_result' => ['nullable', 'string', 'max:191'],
            'acceptance_note' => ['nullable', 'string'],
            'acceptance_confirmed_by' => ['nullable', 'string', 'max:191'],
            'is_confirmed' => ['nullable', 'boolean'],
            'confirmed_at' => ['nullable', 'date'],

            // Người thao tác 
            'created_by' => ['nullable', 'string', 'max:255'],
            'updated_by' => ['nullable', 'string', 'max:255'],
            'completed_by' => ['nullable', 'string', 'max:255'],

            // Reminder / tự động
            'pending_at' => ['nullable', 'date'],
            'processing_at' => ['nullable', 'date'],

            'attachments' => ['nullable','array'],

            'attachments.*' => [
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,mp4,zip,rar'
            ],
        ];
    }
}
